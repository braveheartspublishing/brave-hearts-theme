<?php
/**
 * Brave Hearts Publishing — Meta (Facebook) Pixel, consent-gated.
 *
 * Theme 1.19.203, `CYCLE145-LD-META-PIXEL`. Installed on Andrew Signore's
 * direct order (relayed): "I need this put onto the website code".
 *
 * ⭐ THE PIXEL ID IS PUBLIC BY DESIGN. Every visitor's browser receives it in
 *    clear text on every page; it is not a credential and it is not a secret.
 *    It is hardcoded here — not in an option — for the same reason the GTM
 *    container ID is NOT hardcoded but inverted: the GTM ID had a real
 *    business gate in front of it (`bhp_consent_decision_approved`), whereas
 *    this pixel is the deliverable itself. `bhp_meta_pixel_id` remains as a
 *    filter so a future session can point staging at a separate test pixel
 *    without editing code.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ THE THREE INVARIANTS THIS FILE EXISTS TO HOLD. Break any one and the
 *    install is worse than not having it.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 1. ⛔ NOTHING REACHES META BEFORE THE VISITOR GRANTS MARKETING CONSENT.
 *
 *    ⭐⭐⭐ 2026-08-27 (1.19.312, `CYCLE167-LD-CONSENT-PIXEL-EXT`) — THIS
 *    INVARIANT IS NOW REGION-SCOPED, AND THE HEADLINE SENTENCE ABOVE IS NO
 *    LONGER TRUE OUTSIDE THE EEA/UK. It is preserved rather than rewritten
 *    because the two mechanisms below are UNCHANGED and still describe
 *    exactly what happens on the denied path — what moved is WHICH VISITORS
 *    are on that path.
 *
 *    ⛔ THE INVARIANT AS IT NOW STANDS, and it is the honest wording:
 *
 *        NOTHING REACHES META WHILE THE VISITOR'S EFFECTIVE MARKETING
 *        CONSENT IS DENIED — and it is denied by default for the EEA/UK,
 *        for any visitor the region heuristic cannot confidently place
 *        outside it, for any visitor sending Global Privacy Control, and
 *        for any visitor who has opted out anywhere on earth.
 *
 *    Outside the EEA/UK, absent GPC and absent an opt-out, consent is now
 *    GRANTED BY DEFAULT and the SDK does load on first page view. That is
 *    the founder's ruling (carrier `^349. FOUNDER` item 4, "I guess we
 *    extend it" — RELAYED through the Chief of Staff, not witnessed here),
 *    extending carrier item 310's US-law measurement posture to advertising.
 *    ⛔ It is not a legal opinion; see BHP_Consent::measured_default_signals()
 *    for the full authority note, its limit, and why the opt-out mechanisms
 *    are load-bearing rather than decorative.
 *
 *    ⚠ TWO HONEST CONSEQUENCES, STATED HERE RATHER THAN IN A REPORT THAT
 *      WILL NOT TRAVEL WITH THE CODE:
 *        · The Lighthouse claim below is now true only of a denied visitor.
 *          A US visitor pays the real third-party cost of fbevents.js on
 *          first load, without having clicked anything. Said plainly rather
 *          than left implied by a synthetic number.
 *        · An opt-out cannot un-send what already went. Revoking stops all
 *          FURTHER transmission — proven by observation, see the QA record —
 *          but a PageView fired before the visitor reached the "Privacy
 *          Choices" link has already left the browser. That is inherent to
 *          an opt-out regime and is not a defect in this file.
 *
 *    Two independent layers, either of which alone would be sufficient:
 *
 *      LAYER 1 — Meta's own consent API. `fbq('consent','revoke')` is the
 *      FIRST fbq call, ahead of `init`, exactly as Meta specifies. While
 *      revoked, the SDK holds every event and transmits none.
 *
 *      LAYER 2 — the SDK is not even fetched. Meta's canonical snippet
 *      injects `connect.facebook.net/en_US/fbevents.js` inside the stub. This
 *      build moves that ONE statement into the consent-granted path. Before
 *      consent there is no third-party request, no third-party bytes, and no
 *      third-party code on the page at all — only ~1 KB of inline first-party
 *      JS holding a queue.
 *
 *    Layer 2 is why this costs the Lighthouse score essentially nothing: a
 *    Lighthouse run never accepts a cookie banner, so it never loads
 *    fbevents.js. That is not a trick — it is literally what a non-consenting
 *    visitor experiences. A CONSENTING visitor does pay the real third-party
 *    cost, and that is stated honestly in the release record rather than
 *    hidden behind the synthetic number.
 *
 *    ⚠ THE ONE DELIBERATE DEVIATION FROM META'S SNIPPET, NAMED RATHER THAN
 *    BURIED: the `<noscript><img src="…/tr?id=…&ev=PageView"></noscript>`
 *    beacon is NOT rendered by default. It cannot be consent-gated by any
 *    mechanism that exists: a browser with JavaScript disabled fires it on
 *    parse, and WPConsent — which is JavaScript — can never have run, so such
 *    a visitor can neither grant nor refuse. Server-side gating on the consent
 *    cookie is also unavailable, because that would make the rendered HTML
 *    vary per visitor in front of a full-page cache that varies only on
 *    Accept-Encoding (`CYCLE143-GIM-51`, proven live on production
 *    2026-08-04, in both directions). For a visitor who cannot consent, the
 *    only consent-respecting behaviour is silence. The tag is implemented in
 *    noscript_tag() and can be switched on with the `bhp_meta_pixel_noscript`
 *    filter if Andrew ever decides otherwise.
 *
 * 2. ⛔ THE RENDERED HTML MUST NOT VARY PER VISITOR ON ANY CACHEABLE SURFACE.
 *
 *    This is `CYCLE143-GIM-51` restated. SiteGround's page cache stores
 *    rendered HTML and varies only on Accept-Encoding, so ANY per-visitor
 *    server-rendered byte is served to the wrong visitor eventually. The rule
 *    here is mechanical, not a matter of care:
 *
 *      • Constant-per-URL payloads (PageView, ViewContent) may be inlined
 *        anywhere. A product page's price and ID are the same for everyone.
 *      • Per-visitor payloads (InitiateCheckout's cart value, Purchase's order
 *        total) are emitted ONLY where `DONOTCACHEPAGE` is defined true —
 *        WooCommerce core sets it on `wp` for cart/checkout/account, and
 *        order-received is an endpoint on the checkout page. per_visitor_ok()
 *        checks that constant and FAILS CLOSED. It is not an assumption about
 *        which pages are cached; it is a check on the flag the cache reads.
 *      • Everything else per-visitor — AddToCart, which happens on a POST or a
 *        Store API call and not on a cacheable GET at all — travels in a
 *        short-lived first-party cookie that the runtime consumes and deletes.
 *        A cookie is per-visitor by construction and no page cache can
 *        mis-serve it.
 *
 * 3. ⛔ THE TWO LEAD FUNNELS STAY ISOLATED (`.claude/rules/funnels.md`).
 *
 *    The runtime uses NO localStorage and NO sessionStorage — not one key, for
 *    either funnel. All state is in-memory for one page load. There is
 *    therefore no shared key that a parent-funnel dismissal could write and a
 *    teacher-funnel read, because there is no key at all. Lead events are
 *    distinguished by `content_name` only, mirroring the event prefixes that
 *    already exist (`parent_popup`, `teacher_popup`). tests/test-meta-pixel.php
 *    asserts the absence of both storage APIs in the emitted JS.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHERE EACH STANDARD EVENT COMES FROM
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *   PageView          base code, every page
 *   ViewContent       server, single product page — real ID + real price
 *   AddToCart         server, `woocommerce_add_to_cart` → cookie → JS flush.
 *                     ONE mechanism covering all three add paths, because all
 *                     three end in WC_Cart::add_to_cart(): the classic form
 *                     POST, the collection two-click CTA
 *                     (bhp_bundle_handle_add_to_cart on template_redirect) and
 *                     the Blocks/Store API drawer. Mapping the drawer's own
 *                     dataLayer `add_to_cart` was considered and REJECTED —
 *                     it would double-count the flows that also hit the server
 *                     hook. The drawer's dataLayer push is used only as a
 *                     TRIGGER to re-read the cookie immediately, so a
 *                     no-reload Store API add still reports within the same
 *                     page view rather than on the next navigation.
 *   InitiateCheckout  server, checkout page — real cart total
 *   AddPaymentInfo    the bundle plugin's proven `add_payment_info` dataLayer
 *                     event (assets/bhp-checkout-events.js), which already
 *                     solves the hard part: a MutationObserver for the Blocks
 *                     payment radio, covering both an active change and a
 *                     pre-selected single gateway. `payment_type` is the
 *                     gateway's internal ID; no card data is ever read, by
 *                     that file or this one.
 *   Purchase          server, order-received page — real order total and
 *                     currency FROM THE ORDER, `eventID` = the order number,
 *                     deduplicated by order meta so a refresh cannot re-fire.
 *   Lead              the existing lead dataLayer events, mapped by name.
 *                     ⭐ 1.19.253: the map was INCOMPLETE and had been since
 *                     1.19.203 — it read the two popup success events and the
 *                     inline-form one, and the inline-form one is deliberately
 *                     suppressed for any form that has its own thank-you page,
 *                     which the primary Kit funnel does. The three thank-you /
 *                     quiz success events are now mapped, every Lead carries an
 *                     `eventID` for Conversions-API dedup, and a page load can
 *                     raise at most one Lead. See runtime_config()'s map and
 *                     the latch note in runtime_js().
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Meta_Pixel {

	/**
	 * Meta Pixel ID, supplied by the site owner. Public by design — see the
	 * file docblock. It is not a credential, and no credential store is
	 * involved in reading it.
	 */
	const PIXEL_ID = '2050405642533821';

	/**
	 * Staging behaviour switch. Absent/empty (the default) means: render the
	 * pixel exactly as production does, run the full consent flow, and let
	 * every fbq call accumulate in `fbq.queue` where it can be inspected — but
	 * NEVER fetch fbevents.js, so zero bytes reach Meta from staging.
	 *
	 * Set to `live` for a bounded validation session that needs the real
	 * network path, then unset it. Same pattern, and the same discipline, as
	 * BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE.
	 */
	const OPTION_STAGING_MODE = 'bhp_meta_pixel_staging_mode';

	/** Records the WPConsent service term this theme created, so it is created once and is reversible. */
	const OPTION_SERVICE_TERM = 'bhp_meta_pixel_wpconsent_service_id';

	/** Purchase dedup flag. Deliberately NOT the plugin's `_bhp_purchase_event_fired` — two independent consumers must not share one latch. */
	const PURCHASE_META = '_bhp_meta_pixel_purchase_fired';

	/** Short-lived first-party queue cookie for AddToCart. Contains product IDs, names and prices only. */
	const QUEUE_COOKIE = 'bhp_fbq_q';

	/** WPConsent category slug whose acceptance turns this pixel on. */
	const CONSENT_CATEGORY = 'marketing';

	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'render_head' ), 3 ); // 1 = dataLayer init, 2 = GTM loader
		add_action( 'woocommerce_add_to_cart', array( __CLASS__, 'queue_add_to_cart' ), 99, 4 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_register_consent_service' ) );
	}

	// ─────────────────────────────────────────────────────────────────────
	// Configuration
	// ─────────────────────────────────────────────────────────────────────

	/**
	 * The pixel ID, or '' if it is not a plausible pixel ID. Never invents or
	 * substitutes a value: an empty return means "print nothing", exactly as
	 * BHP_Analytics_Config::gtm_container_id() behaves.
	 */
	public static function pixel_id() {
		$id = trim( (string) apply_filters( 'bhp_meta_pixel_id', self::PIXEL_ID ) );
		return preg_match( '/^[0-9]{8,25}$/', $id ) ? $id : '';
	}

	/**
	 * Whether the pixel renders at all for this request.
	 *
	 * ⛔ Deliberately does NOT read the consent cookie — see invariant 2. The
	 * only inputs are site-wide or cache-exempt, so the answer is constant for
	 * all cacheable traffic on a given environment.
	 */
	public static function should_render() {
		if ( '' === self::pixel_id() ) {
			return false;
		}
		if ( class_exists( 'BHP_Analytics_Config' ) && BHP_Analytics_Config::is_excluded_internal_request() ) {
			return false;
		}
		return (bool) apply_filters( 'bhp_meta_pixel_enabled', true );
	}

	/**
	 * Whether fbevents.js may be fetched once consent is granted.
	 * Production: always. Staging: only under an explicit bounded override.
	 */
	public static function loads_sdk() {
		if ( class_exists( 'BHP_Analytics_Config' ) && BHP_Analytics_Config::is_staging() ) {
			return 'live' === trim( (string) get_option( self::OPTION_STAGING_MODE, '' ) );
		}
		return true;
	}

	/**
	 * Is this request on a surface the page cache is told not to store?
	 *
	 * FAILS CLOSED. WooCommerce core calls wc_maybe_define_constant(
	 * 'DONOTCACHEPAGE', true ) on `wp` for the cart, checkout and account
	 * pages — and order-received is an endpoint on the checkout page, so it is
	 * covered. If that constant is ever absent, per-visitor payloads are simply
	 * not emitted; the pixel under-reports rather than leaking one visitor's
	 * order total into another visitor's cached page.
	 */
	public static function per_visitor_ok() {
		return defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE;
	}

	// ─────────────────────────────────────────────────────────────────────
	// Head rendering
	// ─────────────────────────────────────────────────────────────────────

	public static function render_head() {
		if ( ! self::should_render() ) {
			return;
		}
		echo self::head_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- assembled from escaped/JSON-encoded parts below
	}

	/**
	 * The complete head payload, as a string, so tests can assert on exactly
	 * what a visitor receives rather than on the intent of the code.
	 */
	public static function head_html() {
		$html  = self::base_code_html();
		$html .= sprintf(
			"<script>window.bhpMetaPixel=window.bhpMetaPixel||{};window.bhpMetaPixel.config=%s;window.bhpMetaPixel.events=%s;</script>\n",
			wp_json_encode( self::runtime_config() ),
			wp_json_encode( self::page_events() )
		);
		$html .= sprintf( "<script>%s</script>\n", self::runtime_js() );
		$html .= self::noscript_tag();
		return $html;
	}

	/**
	 * Meta's standard base code, with ONE statement relocated.
	 *
	 * Meta's published snippet is:
	 *
	 *   !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){…};
	 *    …n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;
	 *    s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}
	 *    (window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
	 *   fbq('init', '<ID>');
	 *   fbq('track', 'PageView');
	 *
	 * Two differences, both required by the brief and both deliberate:
	 *
	 *   • `fbq('consent','revoke')` is inserted BEFORE `init`, per Meta's own
	 *     consent documentation. This is an addition, not a change.
	 *   • the four statements that build and insert the <script> element are
	 *     removed from the stub and performed by the runtime at the moment
	 *     marketing consent is granted. The stub's queue semantics are
	 *     untouched: `n.queue=[]` and `n.callMethod?…:n.queue.push(arguments)`
	 *     are byte-for-byte Meta's, so every call made before the SDK arrives
	 *     is held in the same queue Meta's own snippet uses and replays in
	 *     order the instant fbevents.js executes.
	 *
	 * Everything here is constant for every visitor on a given environment.
	 */
	public static function base_code_html() {
		$stub = "!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?"
			. "n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;"
			. "n.push=n;n.loaded=!0;n.version='2.0';n.queue=[]}(window,document,'script');";

		return sprintf(
			"<script>%sfbq('consent','revoke');fbq('init','%s');fbq('track','PageView');</script>\n",
			$stub,
			esc_js( self::pixel_id() )
		);
	}

	/**
	 * The no-JS beacon. Returns '' unless explicitly switched on — see the
	 * file docblock for why. Kept implemented rather than deleted so that
	 * enabling it is a one-line filter and not a re-derivation of Meta's spec.
	 */
	public static function noscript_tag() {
		if ( ! apply_filters( 'bhp_meta_pixel_noscript', false ) ) {
			return '';
		}
		return sprintf(
			'<noscript><img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id=%s&ev=PageView&noscript=1"></noscript>' . "\n",
			rawurlencode( self::pixel_id() )
		);
	}

	/**
	 * Constant per environment. `sdk` is the only value that differs between
	 * staging and production, and it differs by environment rather than by
	 * visitor, so it is cache-safe.
	 */
	public static function runtime_config() {
		return array(
			'sdk'         => self::loads_sdk() ? 'https://connect.facebook.net/en_US/fbevents.js' : '',
			'cookie'      => self::QUEUE_COOKIE,
			'category'    => self::CONSENT_CATEGORY,
			/*
			 * dataLayer event name → the Meta event and content_name it maps
			 * to. A table rather than a chain of ifs so that adding a funnel is
			 * a data change, and so tests can assert the mapping directly.
			 */
			'map'         => array(
				'parent_popup_success'  => array( 'Lead', 'parent_popup' ),
				'teacher_popup_success' => array( 'Lead', 'teacher_popup' ),
				'lead_signup_success'   => array( 'Lead', '' ), // content_name derived from the payload's lead_offer
				/*
				 * ⭐ 1.19.253 (`CYCLE165-LD-META-LEAD-EVENT`). THE THREE ENTRIES
				 * BELOW ARE WHY THIS PIXEL HAD NEVER RECORDED A SINGLE LEAD.
				 * The table above is not wrong — it is incomplete, and it is
				 * incomplete in exactly the place the traffic goes.
				 *
				 * `lead_signup_success` is deliberately NOT fired by any form
				 * that has a dedicated thank-you page: see the comment in
				 * template-parts/acquisition/signup-form.php, which suppresses
				 * it so the thank-you page's own named event is not
				 * double-counted. The Reluctant Reader Adventure Kit — the
				 * primary newsletter funnel — HAS a dedicated thank-you page.
				 * So the main signup path fired `adventure_kit_signup`, which
				 * nothing here read, while `lead_signup_success`, which this
				 * table does read, never fired for it at all. The gift guide
				 * has the same shape, and the quiz's success event was never
				 * mapped either.
				 */
				'adventure_kit_signup'  => array( 'Lead', 'adventure_kit' ),
				'gift_guide_signup'     => array( 'Lead', 'gift_guide' ),
				'quiz_signup_success'   => array( 'Lead', 'quiz' ),
				'add_payment_info'      => array( 'AddPaymentInfo', '' ),
			),
			/* Seeing one of these means the server may have just written the queue cookie. */
			'recheck'     => array( 'add_to_cart' ),
		);
	}

	// ─────────────────────────────────────────────────────────────────────
	// Server-computed page events
	// ─────────────────────────────────────────────────────────────────────

	/**
	 * Every event this particular URL should fire, computed from real
	 * WooCommerce data. Never a literal, never a placeholder: if a value
	 * cannot be read from the product or the order, the event is not emitted.
	 */
	public static function page_events() {
		$events = array();

		if ( ! function_exists( 'is_product' ) || ! function_exists( 'WC' ) ) {
			return $events;
		}

		if ( is_product() ) {
			$view = self::view_content_event();
			if ( $view ) {
				$events[] = $view;
			}
		}

		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			$purchase = self::purchase_event();
			if ( $purchase ) {
				$events[] = $purchase;
			}
			return $events; // order-received is never also "starting" a checkout
		}

		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			$checkout = self::initiate_checkout_event();
			if ( $checkout ) {
				$events[] = $checkout;
			}
		}

		return $events;
	}

	/**
	 * ViewContent for the product actually being viewed.
	 *
	 * Resolved from the queried object rather than the `$product` loop global,
	 * because `wp_head` runs before the WooCommerce content templates set that
	 * global. A variable product with exactly ONE variation reports the
	 * VARIATION id and the variation's own price — the same single-child rule
	 * the theme's Rank Math GTIN patch already uses for the identical reason
	 * (that variation is the offer; the parent is a container).
	 *
	 * @param int $product_id 0 = the queried object. Passed explicitly only by
	 *                        the test suite, which must exercise this against
	 *                        REAL catalog products without faking a main query.
	 */
	public static function view_content_event( $product_id = 0 ) {
		$product = wc_get_product( $product_id ? $product_id : get_queried_object_id() );
		if ( ! $product instanceof WC_Product ) {
			return null;
		}

		$id    = $product->get_id();
		$price = $product->get_price();

		if ( $product->is_type( 'variable' ) ) {
			$children = $product->get_children();
			if ( 1 === count( $children ) ) {
				$variation = wc_get_product( reset( $children ) );
				if ( $variation instanceof WC_Product ) {
					$id    = $variation->get_id();
					$price = $variation->get_price();
				}
			}
		}

		if ( '' === $price || null === $price ) {
			return null; // no real price → no event. Never a zero placeholder.
		}

		return array(
			'name'   => 'ViewContent',
			'params' => array(
				'content_ids'  => array( (string) $id ),
				'content_type' => 'product',
				'content_name' => $product->get_name(),
				'value'        => round( (float) $price, 2 ),
				'currency'     => get_woocommerce_currency(),
			),
		);
	}

	/**
	 * InitiateCheckout with the real cart total. Per-visitor, therefore gated
	 * on per_visitor_ok().
	 */
	public static function initiate_checkout_event() {
		if ( ! self::per_visitor_ok() ) {
			return null;
		}
		$cart = WC()->cart;
		if ( ! $cart || $cart->is_empty() ) {
			return null;
		}

		$ids   = array();
		$items = 0;
		foreach ( $cart->get_cart() as $line ) {
			$ids[]  = (string) ( ! empty( $line['variation_id'] ) ? $line['variation_id'] : $line['product_id'] );
			$items += (int) $line['quantity'];
		}

		return array(
			'name'   => 'InitiateCheckout',
			'params' => array(
				'content_ids'  => array_values( array_unique( $ids ) ),
				'content_type' => 'product',
				'num_items'    => $items,
				'value'        => round( (float) $cart->get_total( 'edit' ), 2 ),
				'currency'     => get_woocommerce_currency(),
			),
		);
	}

	/**
	 * Purchase, with the REAL order total and currency read from the
	 * WC_Order, `eventID` set to the order NUMBER for cross-source dedup, and
	 * an order-meta latch so a refresh of the order-received page — or a
	 * second device opening the same URL — cannot fire it twice.
	 *
	 * The latch is written at render time, which is the same moment the event
	 * is handed to the browser. That is the honest trade: an event lost to a
	 * browser crash between render and execution is not re-sent, which is
	 * strictly better than double-counting revenue.
	 *
	 * ⛔ `get_total()` is used verbatim — the full amount the customer paid,
	 * including tax and shipping, which is what Meta's Purchase `value` means.
	 * That is deliberately NOT the GA4 `value` the bundle plugin computes
	 * (which excludes tax by Phase 7 policy). Two consumers, two documented
	 * definitions, neither derived from the other.
	 *
	 * @param int         $order_id 0 = the `order-received` query var.
	 * @param string|null $key      null = the `key` query arg. Both are passed
	 *                              explicitly only by the test suite, which
	 *                              must render this against a REAL staging
	 *                              order without a real HTTP request.
	 */
	public static function purchase_event( $order_id = 0, $key = null ) {
		if ( ! self::per_visitor_ok() ) {
			return null;
		}

		$order_id = $order_id ? absint( $order_id ) : absint( get_query_var( 'order-received' ) );
		if ( ! $order_id ) {
			return null;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return null;
		}

		// The order key must match, exactly as WooCommerce's own thank-you
		// template requires — never fire a Purchase for an order this visitor
		// merely guessed the ID of.
		if ( null === $key ) {
			$key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( ! $key || ! hash_equals( (string) $order->get_order_key(), (string) $key ) ) {
			return null;
		}

		if ( ! $order->has_status( array( 'processing', 'completed', 'on-hold' ) ) ) {
			return null;
		}

		if ( 'yes' === $order->get_meta( self::PURCHASE_META, true ) ) {
			return null; // already reported — this is a refresh
		}

		// Internal/test orders are excluded by the same provenance classifier
		// the executive dashboard uses, so a staging harness order can never
		// become a reported conversion.
		if ( class_exists( 'BHP_Order_Provenance' ) && ! BHP_Order_Provenance::is_executive_eligible( $order ) ) {
			$order->update_meta_data( self::PURCHASE_META, 'yes' );
			$order->save_meta_data();
			return null;
		}

		$ids   = array();
		$items = 0;
		foreach ( $order->get_items() as $item ) {
			$ids[]  = (string) ( $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id() );
			$items += (int) $item->get_quantity();
		}

		$event = array(
			'name'    => 'Purchase',
			'eventID' => (string) $order->get_order_number(),
			'params'  => array(
				'content_ids'  => array_values( array_unique( $ids ) ),
				'content_type' => 'product',
				'num_items'    => $items,
				'value'        => round( (float) $order->get_total(), 2 ),
				'currency'     => $order->get_currency(),
			),
		);

		$order->update_meta_data( self::PURCHASE_META, 'yes' );
		$order->save_meta_data();

		return $event;
	}

	// ─────────────────────────────────────────────────────────────────────
	// AddToCart — server hook, cookie transport
	// ─────────────────────────────────────────────────────────────────────

	/**
	 * Fires for the classic form POST, the collection two-click CTA and the
	 * Blocks/Store API drawer alike, because all three end in
	 * WC_Cart::add_to_cart(). Writes the event into a first-party cookie the
	 * runtime consumes on the next render; nothing per-visitor is ever put
	 * into cacheable HTML.
	 *
	 * Never throws and never blocks the add: an observability failure must not
	 * cost a sale.
	 */
	public static function queue_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id ) {
		if ( ! self::should_render() || headers_sent() ) {
			return;
		}

		$event = self::add_to_cart_event( $cart_item_key, $product_id, $quantity, $variation_id );
		if ( ! $event ) {
			return;
		}

		$queue   = self::read_queue_cookie();
		$queue[] = $event;

		if ( count( $queue ) > 10 ) {
			$queue = array_slice( $queue, -10 ); // cookies are 4 KB; never let this grow unbounded
		}

		// Path is hardcoded '/' rather than COOKIEPATH so that the delete the
		// runtime performs (which must also name a path, and only knows '/')
		// is guaranteed to match. A path mismatch would leave the cookie
		// undeletable and replay AddToCart on every subsequent page.
		setcookie(
			self::QUEUE_COOKIE,
			wp_json_encode( $queue ),
			0, // session cookie
			'/',
			COOKIE_DOMAIN,
			is_ssl(),
			false // readable by JS by design — this is the transport
		);
	}

	/**
	 * The AddToCart payload, built from the real product record. Separated
	 * from the cookie write so the test suite can assert the VALUES without a
	 * `setcookie()` call it could never read back under WP-CLI.
	 *
	 * Returns null — never a zero-value placeholder — if the product or its
	 * price cannot be read.
	 */
	public static function add_to_cart_event( $cart_item_key, $product_id, $quantity, $variation_id ) {
		$id      = $variation_id ? $variation_id : $product_id;
		$product = wc_get_product( $id );
		if ( ! $product instanceof WC_Product ) {
			return null;
		}
		$price = $product->get_price();
		if ( '' === $price || null === $price ) {
			return null;
		}

		return array(
			'name'    => 'AddToCart',
			// Stable per cart line: a duplicated flush of the same cookie can
			// never be counted twice, because Meta deduplicates on eventID.
			'eventID' => 'atc_' . substr( (string) $cart_item_key, 0, 24 ),
			'params'  => array(
				'content_ids'  => array( (string) $id ),
				'content_type' => 'product',
				'content_name' => $product->get_name(),
				'num_items'    => (int) $quantity,
				'value'        => round( (float) $price * (int) $quantity, 2 ),
				'currency'     => get_woocommerce_currency(),
			),
		);
	}

	private static function read_queue_cookie() {
		if ( empty( $_COOKIE[ self::QUEUE_COOKIE ] ) ) {
			return array();
		}
		$decoded = json_decode( wp_unslash( $_COOKIE[ self::QUEUE_COOKIE ] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		return is_array( $decoded ) ? $decoded : array();
	}

	// ─────────────────────────────────────────────────────────────────────
	// WPConsent service registration
	// ─────────────────────────────────────────────────────────────────────

	/**
	 * Discloses the pixel in the cookie banner's Marketing category.
	 *
	 * The Marketing category currently lists ZERO services, which means the
	 * banner and the generated cookie policy say nothing about what accepting
	 * marketing cookies actually enables. Adding a tracker while leaving that
	 * blank would be the wrong half of the job.
	 *
	 * Runs on `admin_init` only — never on a front-end request, so no visitor
	 * page load can trigger a term insert — and is idempotent three ways: an
	 * option recording the term it created, a re-check that the term still
	 * exists, and a name match against the category's existing services.
	 * Reversible: delete the term and the option.
	 */
	public static function maybe_register_consent_service() {
		if ( ! apply_filters( 'bhp_meta_pixel_register_consent_service', true ) ) {
			return;
		}
		if ( '' === self::pixel_id() || ! function_exists( 'wpconsent' ) ) {
			return;
		}

		$recorded = (int) get_option( self::OPTION_SERVICE_TERM, 0 );
		$cookies  = wpconsent()->cookies;
		if ( ! is_object( $cookies ) || ! method_exists( $cookies, 'add_service' ) ) {
			return;
		}

		$categories = $cookies->get_categories();
		if ( empty( $categories[ self::CONSENT_CATEGORY ]['id'] ) ) {
			return;
		}
		$category_id = (int) $categories[ self::CONSENT_CATEGORY ]['id'];

		$name     = 'Meta Pixel';
		$existing = $cookies->get_services_by_category( $category_id );
		foreach ( (array) $existing as $service ) {
			if ( ( $recorded && (int) $service['id'] === $recorded ) || strtolower( $service['name'] ) === strtolower( $name ) ) {
				update_option( self::OPTION_SERVICE_TERM, (int) $service['id'], false );
				return; // already disclosed
			}
		}

		$term_id = $cookies->add_service(
			$name,
			$category_id,
			'Meta (Facebook) advertising and conversion measurement. Loads only after marketing cookies are accepted; no data is sent to Meta before then.',
			'https://www.facebook.com/privacy/policy/'
		);

		if ( $term_id ) {
			update_option( self::OPTION_SERVICE_TERM, (int) $term_id, false );
		}
	}

	// ─────────────────────────────────────────────────────────────────────
	// The runtime
	// ─────────────────────────────────────────────────────────────────────

	/**
	 * ⛔ STATIC TEXT ONLY. Not one PHP value is interpolated here, for the same
	 * reason BHP_WPConsent_Bridge::bridge_js() is static: the emitted bytes
	 * must not be able to vary by visitor, request or cookie. Everything that
	 * varies arrives through `window.bhpMetaPixel.config` / `.events`, which
	 * are printed separately and audited separately.
	 *
	 * ⛔ NO localStorage. NO sessionStorage. See invariant 3.
	 */
	public static function runtime_js() {
		return <<<'JS'
(function () {
	var NS = window.bhpMetaPixel;
	if ( !NS || NS.started || typeof window.fbq !== 'function' ) { return; }
	NS.started = true;

	var config  = NS.config || {};
	config.map     = config.map || {};
	config.recheck = config.recheck || [];
	var granted = false;
	var loaded  = false;

	/* ---------- Lead identity and the one-Lead-per-page-load latch ----------
	 *
	 * 1.19.253. Two separate problems, one small block.
	 *
	 * ⭐ eventID. Every Lead now carries one. Meta deduplicates on the pair
	 *    (event_name, event_id), so when the server-side Conversions API is
	 *    built it sends the SAME id for the same signup and the two sources
	 *    collapse into one Lead instead of two. Without an id there is nothing
	 *    to dedup on and a CAPI rollout would silently double every lead.
	 *    The id is generated here rather than server-side for a reason that is
	 *    an invariant of this file, not a convenience: the head payload must be
	 *    byte-identical for every visitor (invariant 2), and a per-visitor id
	 *    rendered into cacheable HTML would be served to the wrong visitor.
	 *    It is written back onto the dataLayer payload as `event_id` when the
	 *    payload does not already carry one, so a future GTM/CAPI tag reads the
	 *    same value rather than minting a second.
	 *
	 * ⛔ THE LATCH. A single signup can raise TWO mapped events on one page
	 *    load: the parent popup redirects to `adventure-kit-thank-you`, so that
	 *    page fires `adventure_kit_signup` AND mariana-popup.js fires
	 *    `parent_popup_success` from its pending-submit handoff. One signup is
	 *    one Lead. The first mapped Lead of a page load wins and every later
	 *    one is dropped — a page load is at most one conversion, so this is a
	 *    mechanical rule and not a judgement call.
	 *
	 * ⚠ WHICH ONE IS "FIRST" IS A DELIBERATE ORDERING CONTRACT, NOT LUCK.
	 *    page-adventure-kit-thank-you.php prints its push on `wp_footer` at
	 *    priority 99, which is AFTER wp_print_footer_scripts (priority 20) runs
	 *    mariana-popup.js. The popup's own event therefore arrives first and
	 *    keeps its funnel name and its A/B variant in Meta, and the generic
	 *    thank-you-page event is the fallback for every non-popup signup. If
	 *    either side of that contract moves, attribution degrades to
	 *    `adventure_kit` — the count stays correct either way.
	 *
	 * ⛔ NO storage of any kind is used here. The latch is one in-memory
	 *    boolean for one page load. Invariant 3 is untouched.
	 */
	var leadFired = false;

	function newEventId() {
		var c = window.crypto || window.msCrypto;
		if ( c && typeof c.randomUUID === 'function' ) {
			return c.randomUUID();
		}
		if ( c && typeof c.getRandomValues === 'function' ) {
			var b = new Uint8Array( 16 );
			c.getRandomValues( b );
			b[ 6 ] = ( b[ 6 ] & 0x0f ) | 0x40;
			b[ 8 ] = ( b[ 8 ] & 0x3f ) | 0x80;
			var hex = '';
			for ( var i = 0; i < 16; i++ ) {
				hex += ( b[ i ] + 0x100 ).toString( 16 ).slice( 1 );
				if ( i === 3 || i === 5 || i === 7 || i === 9 ) { hex += '-'; }
			}
			return hex;
		}
		// Last resort only. Not cryptographic, and it does not need to be: an
		// eventID is a correlation key, never a secret and never a credential.
		return 'lead-' + Date.now().toString( 16 ) + '-' + Math.random().toString( 16 ).slice( 2, 14 );
	}

	/* ---------- consent ---------- */

	function readCookie( name ) {
		var m = document.cookie.match( new RegExp( '(?:^|; )' + name + '=([^;]*)' ) );
		return m ? decodeURIComponent( m[ 1 ] ) : null;
	}

	// Marketing consent, and ONLY marketing consent. Statistics consent must
	// never imply advertising consent -- the same separation BHP_Consent keeps
	// between analytics_storage and the three ad_* signals.
	function marketingAccepted( prefs ) {
		return !!( prefs && prefs[ config.category ] === true );
	}

	function storedPreferences() {
		var raw = readCookie( 'wpconsent_preferences' );
		if ( !raw ) { return null; }
		try { return JSON.parse( raw ); } catch ( e ) { return null; }
	}

	/* ══════════════════════════════════════════════════════════════════════
	 * ⭐⭐⭐ 1.19.312 (`CYCLE167-LD-CONSENT-PIXEL-EXT`) — THE PIXEL IS NOW
	 *     CONSENT-STATE-DRIVEN, NOT BANNER-DRIVEN. READ THIS BEFORE TOUCHING
	 *     ANY LINE BELOW IT.
	 * ══════════════════════════════════════════════════════════════════════
	 *
	 * ⛔ THE BUG THIS FIXES IS NOT COSMETIC, AND IT WAS INTRODUCED BY A
	 *    CORRECT RELEASE. Until now the only input to this runtime was the
	 *    `wpconsent_preferences` cookie — i.e. "has the visitor interacted
	 *    with the banner". 1.19.309 (`CYCLE167-LD-CONSENT-BANNER-GEO`) stopped
	 *    showing that banner to non-EEA visitors. A US visitor therefore had
	 *    no cookie, could not get one, and this pixel could NEVER leave the
	 *    revoked state — no SDK, no init, no PageView, no Lead. The founder's
	 *    leads campaign was pointed at a signal path that was structurally
	 *    incapable of firing. Banner-driven consent and an EEA-only banner are
	 *    individually correct and jointly a dead pixel.
	 *
	 * ⭐ THE FIX: read the SAME state Consent Mode reads, from the SAME two
	 *    inputs, in the SAME precedence order as BHP_WPConsent_Bridge. The
	 *    pixel and the Consent Mode defaults must never disagree about one
	 *    visitor, and the only way to guarantee that is to derive both from
	 *    one rule rather than to keep two in sync by care.
	 *
	 *    PRECEDENCE, and it is deliberately identical to the bridge's:
	 *
	 *      1. AN EXPLICIT RECORDED CHOICE ALWAYS WINS, in either direction.
	 *         `wpconsent_preferences` first (the CMP's own cookie is
	 *         authoritative), then `bhp_consent_state` (the bridge's mirror,
	 *         the fallback if the consent surface is ever swapped). A visitor
	 *         who accepted marketing is granted anywhere on earth, EEA
	 *         included. A visitor who opted out is revoked anywhere on earth,
	 *         US included — that is the half that makes this a real opt-out.
	 *      2. NO CHOICE, BUT GPC IS ON -> DENIED. Same rule, same reason, same
	 *         `navigator.globalPrivacyControl` read as the bridge. An opt-out
	 *         preference signal only matters once there is something to opt
	 *         out of by default, and from 1.19.312 there is.
	 *      3. NO CHOICE, NO GPC -> the REGION default, taken from
	 *         `window.bhpConsentRegion.showBanner`, which
	 *         `bhp_consent_region_gate_script()` publishes at wp_head
	 *         priority 1 — two priorities ahead of this file's own head
	 *         render (priority 3), so it is always defined by the time this
	 *         runs. `showBanner === false` means "the heuristic is confident
	 *         this visitor is outside the EEA/UK" -> GRANTED.
	 *
	 * ⛔⛔ THE FAIL-SAFE DIRECTION, AND IT IS INVERTED RELATIVE TO THE BANNER
	 *     SCRIPT. Say this out loud before editing: the banner script shows on
	 *     ambiguity; THIS runtime DENIES on ambiguity. They are not
	 *     inconsistent — both err toward protecting the visitor, and for a
	 *     third-party advertising pixel that means silence. Every path that is
	 *     not an affirmative, confident "outside the EEA" or an affirmative
	 *     recorded accept resolves to DENIED: a missing `bhpConsentRegion`
	 *     (the region gate did not print because the compact banner is
	 *     inactive), a `showBanner` that is not exactly `false`, a thrown
	 *     exception, `UTC`/Tor/resistFingerprinting, a malformed cookie. If a
	 *     future edit makes any branch here return granted on uncertainty, it
	 *     is wrong.
	 *
	 * ⚠ THE ONE DIVERGENCE FROM CONSENT MODE, DISCLOSED RATHER THAN BURIED.
	 *   Google resolves its `region` from the visitor's IP, in the browser, at
	 *   tag-fire time. Meta's fbq has no region concept at all, so this
	 *   runtime must decide client-side, and the only client-side signal
	 *   available is the timezone/locale heuristic from 1.19.309. The two
	 *   therefore disagree for a VPN user: a US visitor on a European exit
	 *   node keeps `America/*` (timezone is an OS setting a VPN does not
	 *   change) and so gets the pixel, while Google sees the European exit IP
	 *   and applies the EEA measurement default. ⭐ THE DIVERGENCE IS NOT
	 *   SYMMETRIC AND THAT IS WHY IT IS ACCEPTABLE IN ONE DIRECTION ONLY: an
	 *   ACTUAL European is protected by the heuristic's own bias, because
	 *   `Europe/*` shows the banner and therefore denies the pixel. The
	 *   population this over-serves is US-resident VPN users, not Europeans.
	 *   The mitigations are the ones already in the tree — the language-subtag
	 *   secondary signal, and the "Privacy Choices" footer link that reaches
	 *   the full preferences UI from every page in every region.
	 *
	 * ⛔ NO STORAGE. Invariant 3 is untouched: this block reads two cookies
	 *    and one already-published global. It writes nothing, and it records
	 *    no choice on a visitor's behalf — a default is not a decision, and
	 *    writing a cookie here would fabricate one.
	 */

	// The bridge's mirror cookie. Marketing consent for pixel purposes is
	// `ad_storage`, which is what BHP_WPConsent_Bridge::toSignals() writes
	// from the same `marketing` category this file reads.
	function mirroredMarketing() {
		var raw = readCookie( 'bhp_consent_state' );
		if ( !raw ) { return null; }
		try {
			var signals = JSON.parse( raw );
			if ( !signals || typeof signals !== 'object' ) { return null; }
			return signals.ad_storage === 'granted';
		} catch ( e ) { return null; }
	}

	function gpcActive() {
		try {
			return window.navigator && window.navigator.globalPrivacyControl === true;
		} catch ( e ) {
			return false;
		}
	}

	// TRUE only for a confident, affirmative "outside the EEA/UK". Anything
	// else -- absent global, non-boolean value, throw -- is ambiguous and
	// resolves to false, which denies.
	function regionAllowsDefault() {
		try {
			var r = window.bhpConsentRegion;
			return !!r && r.showBanner === false;
		} catch ( e ) {
			return false;
		}
	}

	// The single rule. Pure with respect to its argument so the QA harness can
	// drive it with a fixture instead of asserting against a grep of this file.
	//   prefs === undefined -> read the recorded choice from cookies
	//   prefs === an object -> an explicit live choice (a banner save event)
	function effectiveMarketingConsent( prefs ) {
		if ( typeof prefs !== 'undefined' && prefs !== null ) {
			return marketingAccepted( prefs );   // 1. explicit, live
		}
		var stored = storedPreferences();
		if ( stored ) { return marketingAccepted( stored ); }   // 1. explicit, recorded
		var mirrored = mirroredMarketing();
		if ( mirrored !== null ) { return mirrored; }           // 1. explicit, mirrored
		if ( gpcActive() ) { return false; }                    // 2. GPC lowers
		return regionAllowsDefault();                           // 3. region default
	}

	// The ONLY place fbevents.js is ever requested. Before this runs there is
	// no third-party script on the page.
	//
	// ⛔ `onReady` IS LOAD-BEARING AND IS NOT A STYLE CHOICE. It exists because
	// of a defect that queue inspection alone could never have found, and that
	// was reproduced in isolation against Meta's own published snippet:
	//
	//   fbevents.js processes `consent revoke` from the queue and then STOPS
	//   draining. `init` and every queued event stay in the queue. A
	//   `fbq('consent','grant')` issued in the same tick as the script
	//   injection is therefore QUEUED BEHIND the revoke that is blocking the
	//   drain — and can never run. The pixel never initialises, never fetches
	//   its config, and sends nothing, forever.
	//
	//   Measured, four pages, one variable at a time: Meta's canonical snippet
	//   (queue drained, pixel registered) · canonical + revoke/init/PageView +
	//   a later grant (drained, registered) · a late-injected SDK with the
	//   grant queued behind the revoke (queue STUCK at 3, pixels [], NO config
	//   request) · the same but with the grant issued on the script's `onload`
	//   (drained, registered, config fetched).
	//
	//   So the grant must be a LIVE call made after the SDK is executing, not a
	//   queued one. `onerror` also fires the callback: if Meta's CDN is
	//   blocked, the visitor's granted state must still be recorded rather than
	//   left silently half-applied.
	function loadSdk( onReady ) {
		if ( loaded || !config.sdk ) {
			onReady();  // already loading/loaded, or staging queue-only mode
			return;
		}
		loaded = true;
		var s = document.createElement( 'script' );
		s.async = true;
		s.onload = onReady;
		s.onerror = onReady;
		s.src = config.sdk;
		var first = document.getElementsByTagName( 'script' )[ 0 ];
		if ( first && first.parentNode ) {
			first.parentNode.insertBefore( s, first );
		} else {
			document.head.appendChild( s );
		}
	}

	function grant() {
		if ( granted ) { return; }
		granted = true;
		loadSdk( function () {
			/* ⛔ 1.19.312 — RE-CHECK, AND IT IS LOAD-BEARING. `loadSdk` is
			 * ASYNCHRONOUS: the callback runs on the script's onload/onerror,
			 * which can be hundreds of milliseconds after grant() returned.
			 * Before this release a visitor could not revoke inside that
			 * window, because the only way to reach revoke() was a banner
			 * click and the banner had to be open before the SDK was ever
			 * requested. From 1.19.312 the pixel starts granted for a US
			 * visitor on page load, so "opt out while fbevents.js is still in
			 * flight" is now an ORDINARY path rather than an impossible one —
			 * and without this guard the opt-out would be silently undone by
			 * a grant issued from a stale closure. THIS IS THE LINE THAT MAKES
			 * "Privacy Choices genuinely revokes the pixel" true during the
			 * load window as well as after it. */
			if ( !granted ) { return; }
			window.fbq( 'consent', 'grant' );
			flushQueueCookie();
		} );
	}

	function revoke() {
		if ( !granted ) { return; }
		granted = false;
		window.fbq( 'consent', 'revoke' );
	}

	/* 1.19.312. `prefs` is now optional. Called with an object it is an
	 * explicit live choice (a banner save/update event); called with nothing
	 * it means "resolve the effective state from cookies, GPC and region",
	 * which is what the on-load wiring at the bottom of this file does. */
	function applyPreferences( prefs ) {
		if ( effectiveMarketingConsent( prefs ) ) { grant(); } else { revoke(); }
	}

	/* ---------- event emission ---------- */

	// Every event goes through here. Before consent these calls land in
	// fbq.queue -- Meta's own stub queue -- and the SDK that would transmit
	// them has not been fetched, so nothing leaves the browser either way.
	function track( event ) {
		if ( !event || !event.name ) { return; }
		if ( event.eventID ) {
			window.fbq( 'track', event.name, event.params || {}, { eventID: String( event.eventID ) } );
		} else {
			window.fbq( 'track', event.name, event.params || {} );
		}
	}

	( NS.events || [] ).forEach( track );

	/* ---------- AddToCart queue cookie ---------- */

	function clearQueueCookie() {
		document.cookie = config.cookie + '=; Max-Age=0; path=/';
	}

	function flushQueueCookie() {
		var raw = readCookie( config.cookie );
		if ( !raw ) { return; }
		clearQueueCookie();
		var queued;
		try { queued = JSON.parse( raw ); } catch ( e ) { return; }
		if ( !Array.isArray( queued ) ) { return; }
		queued.forEach( track );
	}

	flushQueueCookie();

	/* ---------- dataLayer bridge ---------- */

	function mappingFor( name ) {
		return Object.prototype.hasOwnProperty.call( config.map, name ) ? config.map[ name ] : null;
	}

	// The two popup funnels carry their own fixed name. A landing-page signup
	// carries the funnel in its own payload instead, so the `landing_` prefix
	// keeps it distinguishable from both popups without inventing a name.
	// ⛔ Nothing here reads or writes ANY storage key belonging to either
	// funnel -- see invariant 3.
	function contentNameFor( mapped, payload ) {
		// 1.19.204. An A/B experiment names its own hook, because that is the
		// whole point of running one: 'popup_hook_heartbreak' and
		// 'popup_hook_willing' must be separable in Meta. Nothing in
		// 1.19.203 emits this field, so every existing event keeps the fixed
		// per-funnel name below, byte for byte.
		if ( payload && payload.content_name ) { return String( payload.content_name ); }
		if ( mapped[ 1 ] ) { return mapped[ 1 ]; }
		if ( payload && payload.lead_offer ) { return 'landing_' + String( payload.lead_offer ); }
		return 'landing_page';
	}

	function observe( payload ) {
		if ( !payload || typeof payload !== 'object' ) { return; }
		var name = payload.event;
		if ( !name || typeof name !== 'string' ) { return; }

		if ( config.recheck.indexOf( name ) !== -1 ) {
			// A Store API add does not reload the page, so the cookie the
			// server just wrote would otherwise wait for the next navigation.
			flushQueueCookie();
		}

		var mapped = mappingFor( name );
		if ( !mapped ) { return; }

		var params  = {};
		var eventId = payload.event_id || '';

		if ( 'Lead' === mapped[ 0 ] ) {
			if ( leadFired ) { return; }   // one signup, one Lead — see the latch note above
			leadFired = true;
			params.content_name = contentNameFor( mapped, payload );
			if ( !eventId ) {
				eventId = newEventId();
				// Hand the same id back to whoever else is reading this entry,
				// so a future CAPI/GTM tag dedups against it instead of minting
				// a second id for the same conversion.
				payload.event_id = eventId;
			}
			NS.lead = { name: name, content_name: params.content_name, eventID: eventId };
		}

		if ( payload.currency ) { params.currency = payload.currency; }
		if ( typeof payload.value === 'number' ) { params.value = payload.value; }

		track( { name: mapped[ 0 ], params: params, eventID: eventId } );
	}

	window.dataLayer = window.dataLayer || [];

	// Length-based draining rather than push-interception alone. GTM's
	// container REPLACES `dataLayer.push` with its own when gtm.js executes,
	// which would silently discard a wrapper installed before it. The wrapper
	// below is the fast path; the interval is the guarantee. Both feed the
	// same cursor, so an entry can never be observed twice.
	var seen = 0;
	function drain() {
		var dl = window.dataLayer || [];
		while ( seen < dl.length ) { observe( dl[ seen++ ] ); }
	}
	drain();

	var nativePush = window.dataLayer.push;
	window.dataLayer.push = function () {
		var result = nativePush.apply( this, arguments );
		drain();
		return result;
	};
	window.setInterval( drain, 1000 );

	/* ---------- wiring ---------- */

	/* ⭐ 1.19.312. ON LOAD, resolve the EFFECTIVE state rather than the banner
	 * cookie. The superseded call was `applyPreferences( storedPreferences() )`
	 * — passing null when no cookie existed, which `marketingAccepted( null )`
	 * read as a refusal. That is why an EEA-only banner produced a dead pixel
	 * for every US visitor. Passing NOTHING now means "work it out", and the
	 * cookie is still the first thing effectiveMarketingConsent() looks at, so
	 * a visitor who has actually chosen is unaffected in either direction. */
	applyPreferences();

	/* ON CHOICE: a live banner interaction is an explicit choice and is passed
	 * through as one, so it outranks region and GPC exactly as the recorded
	 * cookie does. `{}` for a save with no detail resolves to denied, which is
	 * the fail-safe direction. */
	window.addEventListener( 'wpconsent_consent_saved', function ( e ) { applyPreferences( e.detail || {} ); } );
	window.addEventListener( 'wpconsent_consent_updated', function ( e ) { applyPreferences( e.detail || {} ); } );

	NS.debug = {
		granted: function () { return granted; },
		sdkLoaded: function () { return loaded; },
		queue: function () { return ( window.fbq && window.fbq.queue ) || []; },
		// 1.19.253. The Lead actually emitted on this page load, or null. QA
		// reads this rather than reconstructing intent from the raw queue.
		lead: function () { return NS.lead || null; },
		/* ⭐ 1.19.312. The consent inputs and the resolved answer, so QA can
		 * observe WHY a visitor was granted or denied instead of inferring it
		 * from the outcome — and so the opt-out path can be proven by
		 * observation rather than by reading this file. Read-only: calling any
		 * of these changes no state and emits no event. */
		consent: function () {
			return {
				effective:     effectiveMarketingConsent(),
				storedChoice:  storedPreferences(),
				mirrored:      mirroredMarketing(),
				gpc:           gpcActive(),
				regionDefault: regionAllowsDefault(),
				regionKnown:   !!window.bhpConsentRegion,
				timeZone:      ( window.bhpConsentRegion && window.bhpConsentRegion.timeZone ) || ''
			};
		}
	};
})();
JS;
	}
}

BHP_Meta_Pixel::init();
