<?php
/**
 * Brave Hearts Bundle Pricing — `WC_Email_BHP_Addon_Thankyou`.
 *
 * The activity-book thank-you and delivery email. Registered by
 * `bhp_bundle_register_addon_thankyou_email()`; do not require this file
 * directly at file scope, because `WC_Email` does not exist until
 * WooCommerce has loaded its own email classes.
 *
 * ⭐ IT IS A REAL `WC_Email` SUBCLASS, NOT A `wp_mail()` CALL, and that is
 *    a deliberate choice with three consequences worth stating:
 *
 *    1. It renders through `emails/email-header.php` and
 *       `emails/email-footer.php`, so it inherits the store's masthead,
 *       colours, inlined CSS and footer without a second copy of any of
 *       them. A hand-rolled `wp_mail()` would have been a second, drifting
 *       email design.
 *    2. It appears in WooCommerce -> Settings -> Emails, so Andrew can see
 *       it, disable it, or change its subject in wp-admin without a
 *       deploy. `WC_Email` reads those settings; this class writes none.
 *    3. `woocommerce_email_subject_bhp_addon_thankyou` and its siblings
 *       exist for free, which is how any future copy override lands.
 *
 * ⛔ NO CUSTOMER-FACING STRING IS DEFINED IN THIS FILE. Every word comes
 *    from `bhp_bundle_addon_thankyou_copy()`. The two `$this->title` /
 *    `$this->description` strings are wp-admin labels for Andrew, not copy
 *    a customer ever sees.
 *
 * @package brave-hearts-bundle-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Email' ) ) {
	return;
}

if ( class_exists( 'WC_Email_BHP_Addon_Thankyou' ) ) {
	return;
}

/**
 * Activity-book thank-you and delivery email.
 */
class WC_Email_BHP_Addon_Thankyou extends WC_Email {

	/**
	 * The add-on download rows for the order being rendered.
	 *
	 * Populated by `trigger()` and read by the templates. Held on the
	 * object rather than recomputed in the template so the email cannot
	 * render a different set of links from the ones the send decision was
	 * made on.
	 *
	 * @var array[]
	 */
	public $addon_downloads = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'bhp_addon_thankyou';
		$this->customer_email = true;
		$this->title          = __( 'Activity book download', 'bhp-bundle-pricing' );
		$this->description    = __( 'Sent to the customer as soon as an order containing the activity book add-on is PAID, and again as a fallback if such an order reaches completed without having been sent. It carries the thank-you and the download link, and it sends at most once per order. It is a second email, in addition to the order emails, which are unchanged.', 'bhp-bundle-pricing' );

		$this->template_html  = 'emails/addon-thankyou.php';
		$this->template_plain = 'emails/plain/addon-thankyou.php';
		$this->template_base  = BHP_BUNDLE_PRICING_DIR . 'templates/';

		$this->placeholders = array(
			'{order_date}'   => '',
			'{order_number}' => '',
		);

		/*
		 * ⚠ PRIORITY 20, NOT 10, AND IT IS LOAD-BEARING. WooCommerce's own
		 *   completed-order email ("Your books have shipped") hooks the
		 *   same action at the default priority. Running after it means the
		 *   two emails arrive in the order Andrew described them: the
		 *   post-purchase expectation email first, the activity-book
		 *   delivery email second.
		 *
		 * ⚠ It also runs after `wc_downloadable_product_permissions()`,
		 *   which core hooks on `woocommerce_order_status_completed` itself
		 *   and which is what makes the signed download URL exist at all.
		 */
		add_action( 'woocommerce_order_status_completed_notification', array( $this, 'trigger' ), 20, 2 );

		/*
		 * ═══════════════════════════════════════════════════════════════
		 * ⭐⭐⭐ 1.8.70 — IT NOW ALSO FIRES AT PURCHASE, NOT ONLY AT
		 *     COMPLETION. THIS IS THE FOUNDER'S RULING, AND THE OLD
		 *     BEHAVIOUR WAS SILENTLY DELIVERING NOTHING.
		 * ═══════════════════════════════════════════════════════════════
		 *
		 * Andrew Signore, 2026-08-24, verbatim (⛔ RELAYED through the Chief
		 * of Staff; NOT witnessed first-hand by the agent that wrote this):
		 *
		 *   "Activity books should be sent via email after purchase."
		 *
		 * ⛔⛔ THE DEFECT THIS CLOSES WAS MEASURED ON PRODUCTION, READ-ONLY,
		 *     2026-08-24 — it was not anticipated from the code.
		 *
		 *     Of the 13 most recent production orders, **12 contain the
		 *     activity book and ALL 13 sit in `processing`**. The newest
		 *     order to reach `completed` is #576, 2026-08-12. Because this
		 *     email hooked `completed` and nothing else, **not one of those
		 *     12 customers has been sent their activity book.** Order #576
		 *     does carry `_bhp_addon_thankyou_sent`, which is the proof
		 *     that the mechanism itself is sound — the TRIGGER was wrong,
		 *     not the email.
		 *
		 *     That is the shape of this store: Andrew hand-delivers school
		 *     orders and ships the rest, so `completed` tracks PHYSICAL
		 *     dispatch and may lag by days or never be set at all. A
		 *     digital file must not wait on a physical act.
		 *
		 * ⛔⛔ THE OBVIOUS HOOK DOES NOT EXIST, AND WRITING IT WOULD HAVE
		 *     SHIPPED A DEAD FILE. `woocommerce_order_status_processing_
		 *     notification` is NEVER FIRED. Read from the running store
		 *     (`WC_Emails::init_transactional_emails()`, WooCommerce 10.9.1)
		 *     rather than assumed: the `$email_actions` list carries
		 *     `woocommerce_order_status_completed` as a BARE status action,
		 *     but every route into `processing` is enumerated as a
		 *     TRANSITION PAIR. Only actions on that list ever get a
		 *     `_notification` twin. So the four hooks below are the
		 *     complete set, and they are exactly the four that core's own
		 *     `WC_Email_Customer_Processing_Order` uses.
		 *
		 * ⚠ ORDERING, CHECKED RATHER THAN ASSUMED. `WC_Order::
		 *   status_transition()` (`includes/class-wc-order.php`) fires
		 *   `woocommerce_order_status_{to}` FIRST and the
		 *   `{from}_to_{to}` pair AFTERWARDS. Core registers
		 *   `wc_downloadable_product_permissions` on the bare
		 *   `woocommerce_order_status_processing`
		 *   (`wc-order-functions.php:495`), so the signed download URL
		 *   already exists by the time these hooks run. `wc_downloadable_
		 *   product_permissions()` does bail on a processing order when
		 *   `woocommerce_downloads_grant_access_after_payment` is `no` —
		 *   that option reads **`yes` on both environments**, verified
		 *   read-only 2026-08-24. ⭐ IT IS STILL NOT TRUSTED: if the grant
		 *   ever fails, `bhp_bundle_addon_thankyou_should_send()` finds no
		 *   download row and DECLINES rather than mailing a broken promise,
		 *   and the `completed` hook above remains as the second chance.
		 *
		 * ⭐ EXACTLY ONE EMAIL PER ORDER, STILL. `BHP_BUNDLE_ADDON_SENT_META`
		 *    is written on a successful handoff to the mailer, so an order
		 *    that goes pending -> processing -> completed sends once, at
		 *    processing. No customer receives a duplicate.
		 *
		 * ⛔ NO WOOCOMMERCE SETTING, PRODUCT, PRICE, COUPON, STOCK OR EMAIL
		 *    OPTION IS WRITTEN BY THIS CHANGE. It is four `add_action`
		 *    calls. Staging remains silenced by `inc/staging-mail-guard.php`,
		 *    which already lists `bhp_addon_thankyou` by id.
		 */
		add_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'trigger' ), 20, 2 );
		add_action( 'woocommerce_order_status_failed_to_processing_notification', array( $this, 'trigger' ), 20, 2 );
		add_action( 'woocommerce_order_status_cancelled_to_processing_notification', array( $this, 'trigger' ), 20, 2 );
		add_action( 'woocommerce_order_status_on-hold_to_processing_notification', array( $this, 'trigger' ), 20, 2 );

		parent::__construct();
	}

	/**
	 * Default subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		$copy = bhp_bundle_addon_thankyou_copy();
		return $copy['email']['subject'];
	}

	/**
	 * Default heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		$copy = bhp_bundle_addon_thankyou_copy();
		return $copy['email']['heading'];
	}

	/**
	 * Send, if this order qualifies.
	 *
	 * ⛔ EVERY DECLINE PATH RETURNS BEFORE `send()`. The order in which the
	 *    checks run is the order in which they are cheapest, and
	 *    `bhp_bundle_addon_thankyou_should_send()` holds all of them in one
	 *    place so a QA report can name which one fired.
	 *
	 * @param int           $order_id Order id.
	 * @param WC_Order|bool $order    Order object, when WooCommerce passes one.
	 * @return void
	 */
	public function trigger( $order_id, $order = false ) {
		$this->setup_locale();

		if ( $order_id && ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}

		if ( $order instanceof WC_Order && bhp_bundle_addon_thankyou_should_send( $order ) ) {
			$this->object    = $order;
			$this->recipient = $order->get_billing_email();

			$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
			$this->placeholders['{order_number}'] = $order->get_order_number();

			$this->addon_downloads = bhp_bundle_addon_order_downloads( $order );

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$sent = $this->send(
					$this->get_recipient(),
					$this->get_subject(),
					$this->get_content(),
					$this->get_headers(),
					$this->get_attachments()
				);

				/*
				 * ⭐ THE MARKER IS WRITTEN ONLY ON A SUCCESSFUL HANDOFF TO
				 *    THE MAILER. If `wp_mail()` returned false the order is
				 *    left unmarked, so re-completing it can legitimately
				 *    retry. Writing the marker unconditionally would turn a
				 *    transient mail failure into a permanently undelivered
				 *    file.
				 *
				 * ⚠ `wp_mail()` returning true means "handed to the
				 *   transport", not "delivered to a human". No claim
				 *   stronger than that is made anywhere in this feature.
				 */
				if ( $sent ) {
					$order->update_meta_data( BHP_BUNDLE_ADDON_SENT_META, current_time( 'mysql' ) );
					$order->save();
				}
			}
		}

		$this->restore_locale();
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
				'order'           => $this->object,
				'email_heading'   => $this->get_heading(),
				'copy'            => bhp_bundle_addon_thankyou_copy(),
				'addon_downloads' => $this->addon_downloads,
				'sent_to_admin'   => false,
				'plain_text'      => false,
				'email'           => $this,
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
				'order'           => $this->object,
				'email_heading'   => $this->get_heading(),
				'copy'            => bhp_bundle_addon_thankyou_copy(),
				'addon_downloads' => $this->addon_downloads,
				'sent_to_admin'   => false,
				'plain_text'      => true,
				'email'           => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Admin settings fields.
	 *
	 * ⚠ Deliberately the standard four and nothing else. There is no
	 *   "additional content" field: this email's whole point is that it is
	 *   short and carries one link, and the store already suppresses
	 *   WooCommerce's stock additional-content filler on every other order
	 *   email (`inc/transactional-emails.php`). Adding the field back here
	 *   would reintroduce exactly what that decision removed.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'    => array(
				'title'   => __( 'Enable/Disable', 'bhp-bundle-pricing' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'bhp-bundle-pricing' ),
				'default' => 'yes',
			),
			'subject'    => array(
				'title'       => __( 'Subject', 'bhp-bundle-pricing' ),
				'type'        => 'text',
				'desc_tip'    => true,
				/* translators: %s: default subject */
				'description' => sprintf( __( 'Defaults to %s', 'bhp-bundle-pricing' ), '<code>' . $this->get_default_subject() . '</code>' ),
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
			),
			'heading'    => array(
				'title'       => __( 'Email heading', 'bhp-bundle-pricing' ),
				'type'        => 'text',
				'desc_tip'    => true,
				/* translators: %s: default heading */
				'description' => sprintf( __( 'Defaults to %s', 'bhp-bundle-pricing' ), '<code>' . $this->get_default_heading() . '</code>' ),
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
			),
			'email_type' => array(
				'title'       => __( 'Email type', 'bhp-bundle-pricing' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'bhp-bundle-pricing' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			),
		);
	}
}
