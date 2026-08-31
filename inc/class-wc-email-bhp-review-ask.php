<?php
/**
 * `WC_Email_BHP_Review_Ask` — the store-sent review ask.
 *
 * Registered by `bhp_review_ask_register_email()` in `inc/review-ask-email.php`.
 * ⛔ Do not require this file at file scope: `WC_Email` does not exist until
 *    WooCommerce has loaded its own email classes.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ IT IS HOOKED TO NOTHING. THAT IS THE MOST IMPORTANT LINE IN THIS FILE.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Every other email class in this store registers itself on an order-status
 * action in its constructor. ⛔ THIS ONE DELIBERATELY DOES NOT. There is no
 * `add_action` anywhere below.
 *
 * The only route to `trigger()` is `bhp_review_ask_send()`, called by the daily
 * runner. So no status change, no wp-admin click, no bulk edit, no import and
 * no plugin that moves orders around can cause this email to fire. ⭐ An engine
 * that emails every buyer must not have a second, forgotten door into it.
 *
 * ⭐ IT IS A REAL `WC_Email` SUBCLASS RATHER THAN A `wp_mail()` CALL, for the
 *    same three reasons `WC_Email_BHP_Addon_Thankyou` is:
 *      1. it renders through `emails/email-header.php` and
 *         `emails/email-footer.php`, so it inherits the store masthead,
 *         colours, inlined CSS and footer instead of being a second, drifting
 *         email design;
 *      2. it appears in WooCommerce -> Settings -> Emails, so Andrew can see it
 *         and switch it off in wp-admin without a deploy;
 *      3. ⭐⭐ it therefore answers `woocommerce_email_enabled_bhp_review_ask`,
 *         which is the exact filter `inc/staging-mail-guard.php` uses. Being a
 *         `WC_Email` is what makes the staging guard cover it AT ALL. A
 *         hand-rolled `wp_mail()` would have walked straight past the guard and
 *         emailed a real customer from staging.
 *
 * ⛔ NO CUSTOMER-FACING STRING IS DEFINED IN THIS FILE. Every word comes from
 *    `bhp_review_ask_copy()`. The `$this->title` / `$this->description` strings
 *    are wp-admin labels for Andrew, not copy a customer ever sees.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Email' ) ) {
	return;
}

if ( class_exists( 'WC_Email_BHP_Review_Ask' ) ) {
	return;
}

/**
 * The T+21 review ask.
 */
class WC_Email_BHP_Review_Ask extends WC_Email {

	/**
	 * The signed opt-out URL for the order being rendered.
	 *
	 * Held on the object rather than recomputed in the template, so the link a
	 * customer receives is provably the one built for the order the send
	 * decision was made on.
	 *
	 * @var string
	 */
	public $optout_url = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = defined( 'BHP_REVIEW_ASK_EMAIL_ID' ) ? BHP_REVIEW_ASK_EMAIL_ID : 'bhp_review_ask';
		$this->customer_email = true;

		$this->title       = __( 'Review ask (T+21 days)', 'brave-hearts' );
		$this->description = __( 'Sent once to every buyer 21 days after their order is completed, asking how the child got on with the book and pointing at the three Amazon review pages. It carries a postal address and a working unsubscribe link. It is sent only by the daily review-ask runner, never by an order status change, and it sends at most once per order and once per customer per 90 days.', 'brave-hearts' );

		$this->template_html  = 'emails/bhp-review-ask.php';
		$this->template_plain = 'emails/plain/bhp-review-ask.php';

		/*
		 * ⭐ THE THEME'S OWN `woocommerce/` DIRECTORY, STATED EXPLICITLY rather
		 *    than relying on `wc_locate_template()` finding a theme override of
		 *    a template that has no plugin original. WooCommerce's default
		 *    `template_base` is its own plugin path, and these two templates do
		 *    not exist there — an implicit lookup would resolve to a missing
		 *    file the day a plugin reordered the template stack.
		 */
		$this->template_base = get_template_directory() . '/woocommerce/';

		$this->placeholders = array(
			'{order_date}'   => '',
			'{order_number}' => '',
			'{site_title}'   => $this->get_blogname(),
		);

		/*
		 * ⛔ NO `add_action` HERE, AND THAT IS DELIBERATE. See this file's
		 *    header. The runner is the only trigger.
		 */

		parent::__construct();
	}

	/**
	 * Default subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		$copy = bhp_review_ask_copy();

		return $copy['subject'];
	}

	/**
	 * Default heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		$copy = bhp_review_ask_copy();

		return $copy['heading'];
	}

	/**
	 * Headers, plus the two RFC 8058 unsubscribe headers.
	 *
	 * ⭐ WHY THESE HEADERS EARN THEIR PLACE. Gmail and Yahoo render a native
	 *    "Unsubscribe" control beside the sender name when they are present,
	 *    and a recipient who uses it is recorded as an unsubscribe rather than
	 *    as a spam complaint. On a sending domain this small, one spam
	 *    complaint is a measurable share of the reputation.
	 *
	 * ⚠ `List-Unsubscribe-Post` promises the URL accepts a POST.
	 *   `bhp_review_ask_handle_optout()` accepts both verbs, so the promise is
	 *   kept. Do not add this header to an email whose endpoint is GET-only.
	 *
	 * @return string
	 */
	public function get_headers() {
		$headers = parent::get_headers();

		if ( '' !== $this->optout_url ) {
			$headers .= 'List-Unsubscribe: <' . esc_url_raw( $this->optout_url ) . ">\r\n";
			$headers .= "List-Unsubscribe-Post: List-Unsubscribe=One-Click\r\n";
		}

		return $headers;
	}

	/**
	 * Send the ask for one order.
	 *
	 * ⛔ IT RE-ASKS `bhp_review_ask_should_send()` EVEN THOUGH THE RUNNER
	 *    ALREADY DID. This method is public and reachable from WP-CLI and from
	 *    the suite; a send path that trusts its caller is a send path that
	 *    eventually emails somebody who opted out.
	 *
	 * ⭐ THE MARKER IS WRITTEN ONLY ON A SUCCESSFUL HANDOFF TO THE MAILER. A
	 *    transient mail failure therefore retries on a later run rather than
	 *    permanently swallowing one customer's ask. Same rule, and the same
	 *    reason, as `WC_Email_BHP_Addon_Thankyou::trigger()`.
	 *
	 * ⚠ `wp_mail()` returning true means "handed to the transport", not
	 *   "delivered to a human". No claim stronger than that is made anywhere in
	 *   this feature, including in the KPI numbers.
	 *
	 * @param int           $order_id Order id.
	 * @param WC_Order|bool $order    Order object, when one is passed.
	 * @return bool True when the mailer accepted the message.
	 */
	public function trigger( $order_id, $order = false ) {
		$this->setup_locale();

		$sent = false;

		if ( $order_id && ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}

		if ( $order instanceof WC_Order && bhp_review_ask_should_send( $order ) ) {
			$this->object     = $order;
			$this->recipient  = $order->get_billing_email();
			$this->optout_url = bhp_review_ask_optout_url( $order );

			$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
			$this->placeholders['{order_number}'] = $order->get_order_number();

			/*
			 * ⛔ NO OPT-OUT URL, NO SEND. The footer's unsubscribe link is the
			 *    compliance mechanism, not decoration. An email that promises an
			 *    unsubscribe and carries a dead link is worse than no email.
			 */
			if ( '' !== $this->optout_url && $this->is_enabled() && $this->get_recipient() ) {
				$sent = $this->send(
					$this->get_recipient(),
					$this->get_subject(),
					$this->get_content(),
					$this->get_headers(),
					$this->get_attachments()
				);

				if ( $sent ) {
					bhp_review_ask_mark_sent( $order );
				}
			}
		}

		$this->restore_locale();

		return (bool) $sent;
	}

	/**
	 * Prepare the object for a render that is NOT a send.
	 *
	 * ⭐ THE QA SEAM, AND IT IS WHY THIS FEATURE COULD BE PROVED ON STAGING AT
	 *    ALL. `inc/staging-mail-guard.php` silences this email id on staging, so
	 *    nothing can be observed by triggering it there. This lets the suite
	 *    render the real `get_content()` through the real templates and write
	 *    the HTML to a file for a human to look at, ⛔ without sending anything,
	 *    without writing the sent marker, and without touching the ledger.
	 *
	 * @param WC_Order|mixed $order Order.
	 * @return bool True when the object is ready to render.
	 */
	public function prepare_preview( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		$this->object     = $order;
		$this->recipient  = $order->get_billing_email();
		$this->optout_url = bhp_review_ask_optout_url( $order );

		$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
		$this->placeholders['{order_number}'] = $order->get_order_number();

		return true;
	}

	/**
	 * HTML body.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order'          => $this->object,
				'email_heading'  => $this->get_heading(),
				'copy'           => bhp_review_ask_copy(),
				'optout_url'     => $this->optout_url,
				'postal_address' => bhp_review_ask_postal_address(),
				'sent_to_admin'  => false,
				'plain_text'     => false,
				'email'          => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Plain-text body.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'          => $this->object,
				'email_heading'  => $this->get_heading(),
				'copy'           => bhp_review_ask_copy(),
				'optout_url'     => $this->optout_url,
				'postal_address' => bhp_review_ask_postal_address(),
				'sent_to_admin'  => false,
				'plain_text'     => true,
				'email'          => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Admin settings fields.
	 *
	 * ⚠ The standard four and nothing else, matching
	 *   `WC_Email_BHP_Addon_Thankyou`. There is deliberately no "additional
	 *   content" field: this email has exactly one ask, and the store already
	 *   suppresses WooCommerce's stock additional-content filler on every order
	 *   email (`inc/transactional-emails.php`). Adding the field back here would
	 *   reintroduce what that decision removed, on the one email where a stray
	 *   second call to action does the most damage.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'    => array(
				'title'   => __( 'Enable/Disable', 'brave-hearts' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'brave-hearts' ),
				'default' => 'yes',
			),
			'subject'    => array(
				'title'       => __( 'Subject', 'brave-hearts' ),
				'type'        => 'text',
				'desc_tip'    => true,
				/* translators: %s: default subject */
				'description' => sprintf( __( 'Defaults to %s', 'brave-hearts' ), '<code>' . $this->get_default_subject() . '</code>' ),
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
			),
			'heading'    => array(
				'title'       => __( 'Email heading', 'brave-hearts' ),
				'type'        => 'text',
				'desc_tip'    => true,
				/* translators: %s: default heading */
				'description' => sprintf( __( 'Defaults to %s', 'brave-hearts' ), '<code>' . $this->get_default_heading() . '</code>' ),
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
			),
			'email_type' => array(
				'title'       => __( 'Email type', 'brave-hearts' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'brave-hearts' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			),
		);
	}
}
