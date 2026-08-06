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
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Meta_Pixel {

	/**
	 * Meta Pixel ID, supplied by Andrew. Public by design — see the file
	 * docblock. Not a credential; `C:\BHP\secrets\` is not involved.
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

	// The ONLY place fbevents.js is ever requested. Before this runs there is
	// no third-party script on the page.
	function loadSdk() {
		if ( loaded || !config.sdk ) { return; }
		loaded = true;
		var s = document.createElement( 'script' );
		s.async = true;
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
		loadSdk();
		window.fbq( 'consent', 'grant' );
		flushQueueCookie();
	}

	function revoke() {
		if ( !granted ) { return; }
		granted = false;
		window.fbq( 'consent', 'revoke' );
	}

	function applyPreferences( prefs ) {
		if ( marketingAccepted( prefs ) ) { grant(); } else { revoke(); }
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

		var params = {};
		if ( 'Lead' === mapped[ 0 ] ) { params.content_name = contentNameFor( mapped, payload ); }
		if ( payload.currency ) { params.currency = payload.currency; }
		if ( typeof payload.value === 'number' ) { params.value = payload.value; }

		track( { name: mapped[ 0 ], params: params, eventID: payload.event_id || '' } );
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

	applyPreferences( storedPreferences() );
	window.addEventListener( 'wpconsent_consent_saved', function ( e ) { applyPreferences( e.detail || {} ); } );
	window.addEventListener( 'wpconsent_consent_updated', function ( e ) { applyPreferences( e.detail || {} ); } );

	NS.debug = {
		granted: function () { return granted; },
		sdkLoaded: function () { return loaded; },
		queue: function () { return ( window.fbq && window.fbq.queue ) || []; }
	};
})();
JS;
	}
}

BHP_Meta_Pixel::init();
