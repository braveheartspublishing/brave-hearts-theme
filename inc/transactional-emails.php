<?php
/**
 * Transactional email COPY layer - E1 through E8.
 *
 * Companion to the template overrides in `woocommerce/emails/` and their
 * `plain/` twins. Everything here is theme code: it writes NO WooCommerce
 * setting, NO option and NO order record, so the whole copy layer reverts
 * with a theme rollback and leaves the store's own email configuration
 * exactly as it was found.
 *
 * Spec: Business OS\WORKING-DRAFTS\commerce-cx\
 *       NIGHT-2026-08-03-EMAIL-PROFESSIONALIZATION-DECK.md  §3 (copy),
 *       §6.2 (this file's mechanism), §2.6 (the two-layer footer).
 *
 * ---------------------------------------------------------------------
 * WHY THE STRINGS LIVE HERE AND NOT IN WooCommerce -> Settings -> Emails
 * ---------------------------------------------------------------------
 * A subject typed into wp-admin is invisible to code review, invisible to
 * git blame, does not travel with a deploy, and drifts between staging and
 * production silently. Deck §6.2. The store currently has no
 * `woocommerce_*_settings` option for any email, so this is a clean start
 * with nothing to migrate.
 *
 * Every callback below DEFERS to a real admin setting when one exists, so
 * that typing a subject into WooCommerce -> Settings -> Emails keeps
 * working and is not silently overridden by code. See
 * `bhp_email_admin_value_wins()` for why an "is it empty?" test is not
 * sufficient.
 *
 * ---------------------------------------------------------------------
 * HARD CONSTRAINTS THAT APPLY TO EVERY STRING IN THIS FILE
 * ---------------------------------------------------------------------
 * ⛔ NO EM DASH (U+2014). Standing email rule.
 * ⛔ NO duration, delivery date, production window or transit-time claim.
 * ⛔ NO tracking number, carrier name or tracking link. Nothing in this
 *    system holds one: the installed Bookvault plugin writes zero tracking
 *    numbers, zero carrier names and zero dispatch events.
 * ⛔ NO coupon, discount, upsell, review ask, lead magnet or unsubscribe
 *    link. Receipts are receipts, and keeping the surface empty is also
 *    what keeps the parent and teacher funnels isolated here.
 * ⛔ NO fabricated review, rating, testimonial, statistic or endorsement.
 *
 * ---------------------------------------------------------------------
 * TWO STRINGS ARE TRUE ONLY BECAUSE OF AN OPERATING RULE
 * ---------------------------------------------------------------------
 * E2 says "Your books have shipped" and E7 says "we are refunding it".
 * Neither is derivable from the database. Both are true only under
 * operating commitments Andrew made when he approved this copy:
 *
 *   E2 (deck Q2): an order is marked Completed in wp-admin only AFTER
 *      Bookvault has been seen to show it dispatched.
 *   E7 (deck Q3): a paid order that is cancelled is refunded at the same
 *      time. "Refund on cancellation while books unprinted."
 *
 * ⛔ IF EITHER OPERATING RULE STOPS BEING FOLLOWED, THE CORRESPONDING
 *    EMAIL BECOMES UNTRUE AND MUST BE SWITCHED TO THE DECK'S A2 VARIANT.
 *    That is a copy change, not a code bug, and it is recorded here so a
 *    future reader knows which sentence depends on which promise.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * The order-email allowlist.
 *
 * Used to scope the FD-76 D6 fulfilment sentence (§2.6 layer 2) to emails
 * that are actually about an order. A password reset or a new-account email
 * carrying "Printed and fulfilled by our publishing partner, Bookvault."
 * would be nonsense to the reader, which is exactly the class of small
 * wrongness this whole workstream exists to remove. `CYCLE142-CX-037`.
 *
 * ⚠ `customer_partially_refunded_order` is NOT a typo and is NOT a
 *    duplicate. WC_Email_Customer_Refunded_Order::trigger() REWRITES its
 *    own `$this->id` to that value for a partial refund, so an allowlist
 *    that only carried `customer_refunded_order` would silently drop the
 *    footer from every partial-refund email.
 *
 * @return string[]
 */
function bhp_email_order_ids() {
	return array(
		'customer_processing_order',
		'customer_completed_order',
		'customer_refunded_order',
		'customer_partially_refunded_order',
		'customer_on_hold_order',
		'customer_failed_order',
		'customer_note',
		'customer_cancelled_order',
	);
}

/**
 * Does a real admin-entered value exist for this email field?
 *
 * An "is it empty?" test is NOT sufficient here, and getting this wrong
 * silently disables the whole filter. WC_Email::get_subject() calls
 * get_option_or_transient( 'subject', $this->get_default_subject() ) BEFORE
 * applying the subject filter, and that call populates
 * $this->settings['subject'] with the class default as a side effect. So by
 * the time a callback runs, get_option( 'subject' ) returns WooCommerce's
 * own stock string even when the store has never configured a subject at
 * all. Measured on staging, WooCommerce 10.9.1.
 *
 * Comparing against the class default is what actually distinguishes
 * "somebody typed a subject in wp-admin" from "WooCommerce filled in its
 * own stock string".
 *
 * @param WC_Email $email    Email object.
 * @param string   $key      Settings key, e.g. 'subject' or 'heading'.
 * @param string   $fallback Explicit default to compare against, for the
 *                           refunded email's partial variants whose
 *                           defaults take an argument.
 * @return bool True when an admin value exists and must win.
 */
function bhp_email_admin_value_wins( $email, $key, $fallback = null ) {
	if ( ! $email instanceof WC_Email ) {
		return false;
	}

	$configured = $email->get_option( $key );

	if ( null !== $fallback ) {
		$default = $fallback;
	} else {
		$getter  = 'get_default_' . $key;
		$default = method_exists( $email, $getter ) ? $email->$getter() : '';
	}

	return ( '' !== $configured && $configured !== $default );
}

/**
 * Resolve one string, deferring to a real admin value.
 *
 * @param string   $current  Value as resolved so far.
 * @param WC_Email $email    Email object.
 * @param string   $key      Settings key.
 * @param string   $new      The Brave Hearts string.
 * @param string   $fallback Explicit class default, where needed.
 * @return string
 */
function bhp_email_resolve( $current, $email, $key, $new, $fallback = null ) {
	if ( bhp_email_admin_value_wins( $email, $key, $fallback ) ) {
		return $current;
	}

	return $email instanceof WC_Email ? $email->format_string( $new ) : $new;
}

/* -------------------------------------------------------------------------
 * SUBJECTS
 * ---------------------------------------------------------------------- */

/**
 * E1, processing order.
 *
 * ⛔ NOT REGISTERED HERE. E1's subject is approved, shipped, and resolved by
 *    `bhp_e1_processing_order_subject()` in `inc/post-purchase-email.php`,
 *    which is left byte-untouched. Registering a second callback for the
 *    same filter would make the winner depend on include order.
 */

/**
 * E2's subject, with the school-visit fork.
 *
 * ⛔ ONE CALLBACK, NOT TWO. The visit branch is INSIDE this closure rather
 *    than registered as a second callback on the same filter, because two
 *    callbacks would make the winner depend on include order - the exact trap
 *    documented for E1 immediately above.
 *
 * ⭐ THE VISIT BRANCH STILL DEFERS TO A REAL ADMIN VALUE, through the same
 *    `bhp_email_resolve()` everything else uses. A subject typed into
 *    WooCommerce -> Settings -> Emails wins over BOTH strings, which is the
 *    behaviour this whole file promises and would be quietly broken by an
 *    early return.
 */
add_filter(
	'woocommerce_email_subject_customer_completed_order',
	function ( $subject, $order = null, $email = null ) {
		$visit = bhp_visit_email_string( $email, 'subject' );

		if ( '' !== $visit ) {
			return bhp_email_resolve( $subject, $email, 'subject', $visit );
		}

		// Deck §3.2 VARIANT A. True only under the mark-complete-after-dispatch rule.
		return bhp_email_resolve( $subject, $email, 'subject', __( 'Your books have shipped', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_subject_customer_refunded_order',
	function ( $subject, $order = null, $email = null ) {
		$partial = ( $email instanceof WC_Email && ! empty( $email->partial_refund ) );

		if ( $partial ) {
			$default = method_exists( $email, 'get_default_subject' ) ? $email->get_default_subject( true ) : null;
			return bhp_email_resolve(
				$subject,
				$email,
				'subject_partial',
				__( 'A partial refund for order #{order_number}', 'brave-hearts' ),
				$default
			);
		}

		return bhp_email_resolve( $subject, $email, 'subject', __( 'Your refund for order #{order_number}', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_subject_customer_on_hold_order',
	function ( $subject, $order = null, $email = null ) {
		return bhp_email_resolve( $subject, $email, 'subject', __( 'Your order is on hold', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_subject_customer_failed_order',
	function ( $subject, $order = null, $email = null ) {
		return bhp_email_resolve( $subject, $email, 'subject', __( 'Your payment did not go through', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_subject_customer_note',
	function ( $subject, $order = null, $email = null ) {
		return bhp_email_resolve( $subject, $email, 'subject', __( 'A note about your order #{order_number}', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_subject_customer_cancelled_order',
	function ( $subject, $order = null, $email = null ) {
		// Deck §3.7 VARIANT A. True only under the cancel-and-refund-together rule.
		return bhp_email_resolve( $subject, $email, 'subject', __( 'Your order has been cancelled', 'brave-hearts' ) );
	},
	10,
	3
);

/* -------------------------------------------------------------------------
 * HEADINGS
 * ---------------------------------------------------------------------- */

/**
 * E1's H1. The ONLY change this file makes to E1's presentation.
 *
 * The stock heading "Thank you for your order" renders at 32px directly
 * above E1's own opening line "Thank you - your order is confirmed." Two
 * thank-yous in the first 40 pixels. `CYCLE142-CX-029`.
 *
 * ⛔ E1's BODY COPY IS APPROVED AND LOCKED AND IS NOT TOUCHED, HERE OR
 *    ANYWHERE IN THIS BUILD. Standing Rules §9.
 */
add_filter(
	'woocommerce_email_heading_customer_processing_order',
	function ( $heading, $order = null, $email = null ) {
		return bhp_email_resolve( $heading, $email, 'heading', __( 'Your order is confirmed', 'brave-hearts' ) );
	},
	10,
	3
);

/**
 * Whether the school-visit email should render no H1 band at all.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.372 · WHY THE DAY-0 HEADING IS SUPPRESSED RATHER THAN MOVED.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ FOUNDER INSTRUCTION, ROUND 11 (⛔ RELAYED through Gandalf, not heard
 *    first-hand): day 0's heading *"must sit in the same position as touch 1"*.
 *
 * ⛔ THE BRIEF'S PREMISE WAS WRONG ON ONE POINT AND THE CORRECTION IS RECORDED
 *    HERE RATHER THAN SILENTLY ABSORBED. The brief said the H1 string is empty
 *    everywhere. It is empty on touch 1, touch 2 and web touch 1 — but NOT on
 *    day 0, which renders *"The signed books went home today"*. Observed in
 *    `REVIEW-SEQ-STAGING\rs370-day0.html` line 30 against
 *    `rs370-touch1.html` line 30, both read at this desk 2026-09-05.
 *
 * ⭐ SO WHAT "THE SAME POSITION AS TOUCH 1" ACTUALLY RESOLVES TO. Touch 1
 *    renders NO VISIBLE HEADING. Matching it means day 0 renders none either,
 *    which is what this filter does. ⛔ NOTHING IS LOST: the same sentence is
 *    the subject line and the preheader, so it still introduces the email in
 *    the inbox — it simply stops being repeated as a banner above a hero
 *    photograph that already opens the message.
 *
 * ⛔ WHY NOT JUST SET `heading` TO '' IN THE COPY SET. Because
 *    `bhp_visit_email_copy_is_usable()` REQUIRES A NON-EMPTY HEADING, and an
 *    empty string there would silently discard the entire approved day-0 set
 *    and fall the order back to the ordinary completed-order email. The copy
 *    set keeps its string; the suppression happens at render.
 *
 * ⚠ IT IS FILTERABLE AND REVERSIBLE IN ONE LINE. Return false from
 *   `bhp_visit_email_suppress_heading` and the banner comes back.
 *
 * @since 1.19.372
 * @param WC_Email|mixed $email Email object.
 * @return bool
 */
function bhp_visit_email_suppress_heading( $email ) {
	if ( ! function_exists( 'bhp_visit_email_body' ) ) {
		return false;
	}

	$suppress = ! empty( bhp_visit_email_body( $email ) );

	/**
	 * Filter whether the school-visit email suppresses its H1 band.
	 *
	 * @since 1.19.372
	 * @param bool           $suppress True to render no heading.
	 * @param WC_Email|mixed $email    Email object.
	 */
	return (bool) apply_filters( 'bhp_visit_email_suppress_heading', $suppress, $email );
}

/** E2's H1, with the school-visit fork. Same single-callback rule as the subject. */
add_filter(
	'woocommerce_email_heading_customer_completed_order',
	function ( $heading, $order = null, $email = null ) {
		/*
		 * ⭐ 1.19.372 · THE VISIT EMAIL RENDERS NO H1. See
		 *    `bhp_visit_email_suppress_heading()` for the whole reasoning; the
		 *    empty string is then removed as a band, not emitted as padding,
		 *    by `bhp_email_strip_empty_heading()`.
		 */
		if ( bhp_visit_email_suppress_heading( $email ) ) {
			return '';
		}

		$visit = bhp_visit_email_string( $email, 'heading' );

		if ( '' !== $visit ) {
			return bhp_email_resolve( $heading, $email, 'heading', $visit );
		}

		return bhp_email_resolve( $heading, $email, 'heading', __( 'Your books have shipped', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_heading_customer_refunded_order',
	function ( $heading, $order = null, $email = null ) {
		$partial = ( $email instanceof WC_Email && ! empty( $email->partial_refund ) );

		if ( $partial ) {
			$default = method_exists( $email, 'get_default_heading' ) ? $email->get_default_heading( true ) : null;
			return bhp_email_resolve(
				$heading,
				$email,
				'heading_partial',
				__( 'We have refunded part of your order', 'brave-hearts' ),
				$default
			);
		}

		return bhp_email_resolve( $heading, $email, 'heading', __( 'We have refunded your order', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_heading_customer_on_hold_order',
	function ( $heading, $order = null, $email = null ) {
		return bhp_email_resolve( $heading, $email, 'heading', __( 'Your order is on hold', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_heading_customer_failed_order',
	function ( $heading, $order = null, $email = null ) {
		return bhp_email_resolve( $heading, $email, 'heading', __( 'Your payment did not go through', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_heading_customer_note',
	function ( $heading, $order = null, $email = null ) {
		return bhp_email_resolve( $heading, $email, 'heading', __( 'A note about your order', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_heading_customer_cancelled_order',
	function ( $heading, $order = null, $email = null ) {
		return bhp_email_resolve( $heading, $email, 'heading', __( 'Your order has been cancelled', 'brave-hearts' ) );
	},
	10,
	3
);

/* -------------------------------------------------------------------------
 * ADDITIONAL CONTENT - suppressed on every order email
 * ---------------------------------------------------------------------- */

/**
 * Kill WooCommerce's stock `additional_content` filler on every order email.
 *
 * `CYCLE142-CX-028`. On E1 it currently renders as the very last thing the
 * reader sees, BELOW Andrew's signature and BELOW the order-number fine
 * print:
 *
 *   "Thanks again! If you need any help with your order, please contact us
 *    at Andrew@braveheartspublishing.com."
 *
 * It is a WooCommerce class default. Nobody wrote it. It duplicates E1's own
 * better line ("Reply to this email - it comes to a real person") and it puts
 * a stock-sounding sentence in the last position. The same string, or a near
 * twin, is the class default on all six of the other order emails.
 *
 * ⚠ An admin-entered value still wins, so this suppresses the DEFAULT and
 *   not a deliberate choice somebody made in wp-admin.
 */
foreach ( bhp_email_order_ids() as $bhp_email_id ) {
	add_filter(
		'woocommerce_email_additional_content_' . $bhp_email_id,
		function ( $content, $order = null, $email = null ) {
			if ( bhp_email_admin_value_wins( $email, 'additional_content' ) ) {
				return $content;
			}
			return '';
		},
		10,
		3
	);
}
unset( $bhp_email_id );

/* -------------------------------------------------------------------------
 * PREHEADERS - the inbox preview line
 * ---------------------------------------------------------------------- */

/**
 * Preheader text, per email id.
 *
 * @return array<string,string>
 */
function bhp_email_preheaders() {
	$preheaders = array(
		// E1's preheader is already approved and shipped. Carried verbatim.
		'customer_processing_order'         => __( 'A quick note on how your books are made, and when to expect us again.', 'brave-hearts' ),
		'customer_completed_order'          => __( 'They are on their way to you now.', 'brave-hearts' ),
		'customer_refunded_order'           => __( 'The full amount is on its way back to you.', 'brave-hearts' ),
		'customer_partially_refunded_order' => __( 'Part of your order has been refunded.', 'brave-hearts' ),
		'customer_on_hold_order'            => __( 'Nothing is printing yet. We will email you when it moves.', 'brave-hearts' ),
		'customer_failed_order'             => __( 'Nothing was charged. You can try again here.', 'brave-hearts' ),
		'customer_note'                     => __( 'A quick update from us.', 'brave-hearts' ),
		'customer_cancelled_order'          => __( 'Nothing will be printed or sent.', 'brave-hearts' ),
	);

	/*
	 * ⭐ 1.19.317 — THE REVIEW ASK'S PREHEADER JOINS THE SAME MAP, AND IT IS
	 *    PULLED FROM THAT FEATURE'S COPY ARRAY RATHER THAN RETYPED.
	 *
	 * ⛔ WHY IT IS HERE RATHER THAN IN A SECOND `woocommerce_mail_content`
	 *    FILTER OF ITS OWN: `bhp_email_inject_preheader()` PATCHES THE
	 *    ASSEMBLED HTML AND UNSETS ITS MARKER GLOBAL. A second injector on the
	 *    same filter would either double-inject a hidden div or race the first
	 *    one for the global, and which won would depend on include order. One
	 *    injector, one map, one entry per email id.
	 *
	 * ⚠ `function_exists` guarded because this file is required BEFORE
	 *   `inc/review-ask-email.php`. The map is built at render time, long after
	 *   both are loaded, so the guard is belt and braces rather than the
	 *   mechanism.
	 */
	if ( function_exists( 'bhp_review_ask_copy' ) && defined( 'BHP_REVIEW_ASK_EMAIL_ID' ) ) {
		$bhp_review_copy = bhp_review_ask_copy();

		if ( ! empty( $bhp_review_copy['preheader'] ) ) {
			$preheaders[ BHP_REVIEW_ASK_EMAIL_ID ] = (string) $bhp_review_copy['preheader'];
		}
	}

	return $preheaders;
}

/**
 * Capture the identity of the email currently rendering.
 *
 * `woocommerce_mail_content` carries no email identity, so the identity is
 * captured here - `woocommerce_email_header` does receive the email object,
 * and it always fires during the same render, before the content filter.
 *
 * @param string   $email_heading Heading text.
 * @param WC_Email $email         Email object.
 * @return void
 */
function bhp_email_mark_rendering( $email_heading, $email = null ) {
	if ( $email instanceof WC_Email ) {
		$GLOBALS['bhp_rendering_email_id'] = $email->id;

		/*
		 * ⭐ THE VISIT SLUG IS CAPTURED HERE FOR THE SAME REASON THE ID IS.
		 *    `woocommerce_mail_content` receives ONLY the assembled HTML - no
		 *    email object, no order - so by the time the preheader is injected
		 *    there is nothing left to ask. This action does receive `$email`,
		 *    and `$email->object` is still the order, so the question is
		 *    answered here and the answer is carried forward.
		 *
		 * ⛔ ALWAYS SET, INCLUDING TO ''. Leaving the key untouched on a
		 *    non-visit render would let a previous render's slug survive into
		 *    the next one inside a single request - the stale-global defect
		 *    that the `unset()` in the injector already guards against for the
		 *    id, and the reason a WP-CLI loop over several orders is the
		 *    dangerous case rather than a browser request.
		 */
		$GLOBALS['bhp_rendering_email_visit_slug'] = bhp_visit_email_slug( $email );

		/*
		 * ⭐ 1.19.362 — THE REVIEW ASK'S PREHEADER IS CAPTURED HERE FOR EXACTLY
		 *    THE REASON THE VISIT SLUG IS, and this fixes a real defect the
		 *    seal-965 sequence introduced.
		 *
		 * ⛔ THE MAP BELOW CANNOT ANSWER THIS ANY MORE. It is keyed by email
		 *    ID, and since seal 965 one ID renders TWO different emails - touch
		 *    1 and the touch-2 reminder - with different preheaders. A map
		 *    lookup by ID would have put touch 1's preview text in the inbox
		 *    beside touch 2's subject line, which is the kind of mismatch a
		 *    reader notices and nobody tests for.
		 *
		 * ⭐ `$email->touch` and `$email->object` are both still set at header
		 *    time, so the question is answered while the answer exists and is
		 *    carried forward, exactly as the slug is.
		 *
		 * ⛔ ALWAYS SET, INCLUDING TO '', for the stale-global reason above.
		 */
		$GLOBALS['bhp_rendering_email_preheader'] = '';

		if ( defined( 'BHP_REVIEW_ASK_EMAIL_ID' ) && BHP_REVIEW_ASK_EMAIL_ID === $email->id
			&& function_exists( 'bhp_review_ask_copy' ) ) {
			$bhp_ra_touch = isset( $email->touch ) ? (int) $email->touch : 1;
			$bhp_ra_order = ( isset( $email->object ) && $email->object instanceof WC_Order ) ? $email->object : null;
			$bhp_ra_copy  = bhp_review_ask_copy( $bhp_ra_touch, $bhp_ra_order );

			if ( ! empty( $bhp_ra_copy['preheader'] ) ) {
				$GLOBALS['bhp_rendering_email_preheader'] = (string) $bhp_ra_copy['preheader'];
			}
		}
	}
}
add_action( 'woocommerce_email_header', 'bhp_email_mark_rendering', 1, 2 );

/**
 * Inject the preheader (inbox preview text) into the assembled HTML.
 *
 * WooCommerce's `email-header.php` has no preheader slot, and anything
 * echoed on `woocommerce_email_header` lands either before the doctype
 * (priority < 10) or after the masthead (priority > 10), neither of which a
 * mail client reads as preview text. The reliable place is immediately
 * inside `<body>`, so the assembled HTML is patched there, once.
 *
 * The trailing zero-width joiners are the standard preview-text padding:
 * they stop the client spilling the first paragraph of body copy into the
 * inbox preview after the preheader ends.
 *
 * @param string $content Fully assembled, style-inlined email HTML.
 * @return string
 */
function bhp_email_inject_preheader( $content ) {
	if ( empty( $GLOBALS['bhp_rendering_email_id'] ) ) {
		return $content;
	}

	$id = $GLOBALS['bhp_rendering_email_id'];
	unset( $GLOBALS['bhp_rendering_email_id'] );

	$visit_slug = isset( $GLOBALS['bhp_rendering_email_visit_slug'] )
		? (string) $GLOBALS['bhp_rendering_email_visit_slug']
		: '';
	unset( $GLOBALS['bhp_rendering_email_visit_slug'] );

	$captured_preheader = isset( $GLOBALS['bhp_rendering_email_preheader'] )
		? (string) $GLOBALS['bhp_rendering_email_preheader']
		: '';
	unset( $GLOBALS['bhp_rendering_email_preheader'] );

	$preheaders = bhp_email_preheaders();

	/*
	 * ⭐ 1.19.362 — A PREHEADER CAPTURED FROM THE LIVE EMAIL OBJECT OUTRANKS
	 *    THE MAP, and the same "applied after the lookup, not by mutating the
	 *    map" discipline as the visit override below is kept deliberately, so
	 *    the map stays a plain readable list and each fork stays visible.
	 *
	 * ⛔ ONLY THE REVIEW ASK EVER SETS IT (see `bhp_email_mark_rendering()`),
	 *    because it is the only email id in this store that renders two
	 *    different messages.
	 */
	if ( '' !== $captured_preheader ) {
		$preheaders[ $id ] = $captured_preheader;
	}

	/*
	 * ⭐ THE SCHOOL-VISIT PREHEADER REPLACES E2's, AND ONLY E2's. The map above
	 *    is keyed by email id, so this override is applied after the lookup
	 *    rather than by mutating the map - the map stays a plain, readable
	 *    list of the seven standing strings, and the fork stays visible.
	 *
	 * ⛔ THE SLUG IS ONLY EVER NON-EMPTY FOR `customer_completed_order`:
	 *    `bhp_visit_email_order()` returns null for every other email id, so
	 *    no other preheader can be reached from here.
	 */
	if ( '' !== $visit_slug ) {
		$visit_copy = bhp_visit_email_copy( $visit_slug );
		if ( isset( $visit_copy['preheader'] ) && is_string( $visit_copy['preheader'] ) && '' !== $visit_copy['preheader'] ) {
			$preheaders[ $id ] = $visit_copy['preheader'];
		}
	}

	if ( ! isset( $preheaders[ $id ] ) ) {
		return $content;
	}

	if ( false === strpos( $content, '<body' ) ) {
		return $content; // Plain-text or an unexpected wrapper - leave untouched.
	}

	$preheader = '<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;color:transparent;">'
		. esc_html( $preheaders[ $id ] )
		. str_repeat( '&#8204;&nbsp;', 60 )
		. '</div>';

	return preg_replace( '/(<body\b[^>]*>)/i', '$1' . $preheader, $content, 1 );
}
add_filter( 'woocommerce_mail_content', 'bhp_email_inject_preheader', 20 );

/* -------------------------------------------------------------------------
 * FOOTER - layer 2, order emails only
 * ---------------------------------------------------------------------- */

/**
 * The FD-76 D6 fulfilment sentence and the reply route.
 *
 * Provenance, checked at source rather than accepted from a brief: FD-76
 * limb D6, `Business OS\FOUNDER-DECISIONS-2026-08-01.md` line 2761. Andrew,
 * verbatim: "One fulfilment sentence sitewide: 'Printed and fulfilled by our
 * publishing partner, Bookvault', matching the privacy-policy naming.
 * Retires 'Printed and shipped by Bookvault'." The identical string with a
 * full stop is already enforced sitewide by `inc/audit-remediation.php`.
 *
 * @return string[] Two sentences, in render order.
 */
function bhp_email_footer_note_lines() {
	return array(
		__( 'Printed and fulfilled by our publishing partner, Bookvault.', 'brave-hearts' ),
		__( 'Reply to this email and it comes to a real person.', 'brave-hearts' ),
	);
}

/**
 * The footer-note lines for ONE email, with the school-visit fulfilment
 * sentence removed.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THE BOOKVAULT SENTENCE IS FALSE FOR A HAND-DELIVERED VISIT ORDER, AND
 *     DROPPING IT IS A FOUNDER RULING, NOT AN ENGINEERING PREFERENCE.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Carrier item 377 ruling 1: *"the false Bookvault fulfilment footer
 * DROPPED for visit orders, standard shipped orders untouched."* Nothing was
 * printed by Bookvault for these orders - `_bhp_school_pickup_bv_skipped` is
 * set on every one of the eight Adams orders, verified read-only on production
 * 2026-08-28 - so the sentence would tell a parent something that did not
 * happen.
 *
 * ⭐ THE REPLY ROUTE SURVIVES. It is the one line in that footer that is true
 *    on every order and the only route a parent has back to a person.
 *
 * ⛔ `bhp_email_footer_note_lines()` ABOVE IS LEFT BYTE-UNTOUCHED and is still
 *    the source of both strings. This function subtracts; it never rewrites,
 *    and it can never add a line the standard footer does not already carry.
 *
 * @param WC_Email|null $email Email object.
 * @return string[] Lines in render order.
 */
function bhp_email_footer_note_lines_for( $email = null ) {
	$lines = bhp_email_footer_note_lines();

	if ( ! bhp_visit_email_is_visit( $email ) ) {
		return $lines;
	}

	// Drop the fulfilment sentence; keep everything after it, in order.
	return array_values( array_slice( $lines, 1 ) );
}

/**
 * Render the order-email footer note (HTML path).
 *
 * ⛔ WHY THIS IS SCOPED AND NOT PUT IN THE GLOBAL FOOTER OPTION.
 *    `woocommerce_email_footer_text`'s stored value is global. Putting a
 *    print-fulfilment sentence in the OPTION would mean a password-reset
 *    email and a new-account email both carry "Printed and fulfilled by our
 *    publishing partner, Bookvault." `CYCLE142-CX-037`.
 *
 * ⛔ WHY THIS IS AN ACTION AND NOT THE `woocommerce_email_footer_text`
 *    FILTER, MEASURED RATHER THAN ASSUMED. That filter LOOKS scopable:
 *    `emails/email-footer.php` calls it as
 *    `apply_filters( 'woocommerce_email_footer_text', $text, $email )`. But
 *    `WC_Emails::email_footer()` is declared `function email_footer()` with
 *    NO parameters and calls `wc_get_template( 'emails/email-footer.php' )`
 *    with NO arguments, so the template's own `$email = $email ?? null;`
 *    resolves to null on EVERY render. Read from
 *    `includes/class-wc-emails.php:388` on staging, WooCommerce 10.9.1, and
 *    confirmed by a first build in which the sentence appeared in all seven
 *    plain twins and in none of the HTML siblings.
 *    ➡ The `woocommerce_email_footer` ACTION does receive `$email`, because
 *      every template fires it as `do_action( 'woocommerce_email_footer',
 *      $email )`. That is what this hooks.
 *
 * Priority 5 puts this above WooCommerce's own footer template (priority
 * 10), so it renders at the end of the body card, immediately below the
 * order-number fine print and immediately above the store name and address.
 * That is the same position, in the same order, as the plain-text twins.
 *
 * ⛔ DO NOT "SIMPLIFY" THIS BY OVERRIDING emails/email-header.php OR
 *    emails/email-footer.php. The `email_improvements` feature flag is
 *    enabled on this store and rewrites both; an override pins the theme to
 *    one branch of core and diverges silently on the next update. Deck §6.3.
 *
 * @param WC_Email|null $email Email object.
 * @return void
 */
function bhp_email_footer_note_html( $email = null ) {
	if ( ! $email instanceof WC_Email ) {
		return;
	}

	if ( ! in_array( $email->id, bhp_email_order_ids(), true ) ) {
		return;
	}

	$lines = bhp_email_footer_note_lines_for( $email );

	if ( empty( $lines ) ) {
		return;
	}

	/*
	 * ⚠ JOINED WITH A LOOP RATHER THAN `$lines[0] . '<br>' . $lines[1]`, WHICH
	 *   IS WHAT THIS WAS. The visit fork makes the array one element shorter,
	 *   and the old indexed form would have emitted an undefined-index notice
	 *   and a trailing `<br>` on every visit email. Same rendered output,
	 *   byte for byte, on the two-line standard path.
	 */
	$escaped = array_map( 'esc_html', $lines );

	echo '<hr style="border:none;border-top:1px solid #e5e0d3;margin:28px 0 14px;">';
	echo '<p style="font-size:12px;color:#6b6b60;margin:0;"><small>'
		. implode( '<br>', $escaped ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each element escaped above.
		. '</small></p>';
}
add_action( 'woocommerce_email_footer', 'bhp_email_footer_note_html', 5 );

/**
 * Plain-text footer for every Brave Hearts order-email plain twin.
 *
 * Echoes the two footer-note lines, then the store's global footer text,
 * so the plain reader gets exactly the promises the HTML reader gets.
 *
 * Called directly from the `plain/` templates. It does NOT run the
 * `woocommerce_email_footer_text` filter with an `$email` argument, because
 * `bhp_email_footer_note_html()` would then add the same two lines a second
 * time.
 *
 * @param WC_Email|null $email Email object.
 * @return void
 */
function bhp_email_plain_footer( $email = null ) {
	$is_order_email = ( $email instanceof WC_Email && in_array( $email->id, bhp_email_order_ids(), true ) );

	if ( $is_order_email ) {
		// The school-visit fork drops the Bookvault sentence here too, so the
		// plain reader gets exactly the promises the HTML reader gets.
		foreach ( bhp_email_footer_note_lines_for( $email ) as $line ) {
			echo esc_html( $line ) . "\n";
		}
		echo "\n";
	}

	/*
	 * ⚠ `<br />` REPAIRED, NOT INHERITED. The stored footer option is
	 *   `{site_title}<br />{store_address}`. WC_Email::get_content() runs
	 *   `wp_strip_all_tags()` over the plain-text body, which DELETES the
	 *   `<br />` outright rather than turning it into a line break, so
	 *   WooCommerce's own plain templates render
	 *   "Brave Hearts Publishing580 Hyde Ave, Pocatello..." with the store
	 *   name welded to the street number.
	 *
	 *   OBSERVED, not assumed: it is present in the pre-change baseline
	 *   render of E1's plain twin taken on staging 1.19.155 before this
	 *   build, so it is a pre-existing defect this build repairs rather
	 *   than one it introduced. Converting `<br>` to a newline BEFORE the
	 *   strip is the whole fix.
	 *
	 * One filter argument only, matching WooCommerce's own plain templates.
	 */
	$footer_text = apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
	$footer_text = preg_replace( '#<br\s*/?>#i', "\n", $footer_text );

	echo wp_kses_post( $footer_text );
}

/* -------------------------------------------------------------------------
 * BRAND CSS LAYER
 * ---------------------------------------------------------------------- */

/**
 * Brand typography and a mobile guard, appended to WooCommerce's email CSS.
 *
 * ⚠ `CYCLE142-CX-036`. Webfonts do not render in Gmail, Outlook desktop or
 *   several other major clients. EB Garamond WILL resolve to Georgia for a
 *   large share of recipients and no `@font-face` changes that. This is a
 *   graceful-degradation stack, not a guarantee, and saying otherwise would
 *   be claiming a result nobody observed. Georgia is legitimate AS THE
 *   FALLBACK in a stack, never as the intended face.
 *
 * ⛔ Do not attempt to load the theme's local variable font files into
 *    email. The bracket-filename trap (`Archivo[wdth,wght].ttf`) fails
 *    silently in a browser and would fail silently here too.
 *
 * `@media` blocks cannot be inlined; WooCommerce preserves them in a
 * `<style>` element instead. The assembled output is checked by counting
 * `@media` occurrences, not by trusting that this filter fired.
 *
 * ⛔ Gold `#D9A45F` appears here NOT AT ALL. On the ivory and parchment
 *    grounds of an email body gold measures 1.81:1 to 2.14:1 and fails
 *    everything. It appears in exactly two places in this build: inside the
 *    masthead image, and as the 1.5px BORDER on E5's button. Never as a
 *    colour value. Kit §1.4.
 *
 * @param string $css Existing email CSS.
 * @return string
 */
function bhp_email_brand_styles( $css ) {
	$css .= '
#template_header h1,
#body_content_inner h1,
#body_content_inner h2,
#body_content_inner h3 {
	font-family: "EB Garamond", Georgia, "Times New Roman", serif;
	letter-spacing: normal;
}

/*
 * ⭐ 1.19.370 · H1 AT 30px, NOT 32px. `CYCLE179-DES-REVIEW-EMAIL.md` §5: at
 *    32px the approved H1 line wrapped awkwardly against the 536px hero above
 *    it. ⚠ Legolas measured that against a rendered preview; it is not
 *    re-measured here.
 */
#template_header h1 {
	font-size: 30px;
}

#body_content_inner {
	font-size: 16px;
	line-height: 1.6;
}

@media only screen and (max-width: 400px) {
	#body_content_inner {
		font-size: 16px !important;
	}
	#template_header h1 {
		font-size: 26px !important;
	}
}

/*
 * ⭐⭐ 1.19.372 · THE ORDER AND DOWNLOADS TABLES AT 375px.
 *
 * ⛔ WHAT WAS OBSERVED, IN A RENDER, NOT REASONED ABOUT. Legolas rendered the
 *    1.19.370 day-0 email at 375 CSS px (`render-370-day0-375.png`, read at
 *    this desk 2026-09-05). Two failures, both caused by the same thing —
 *    RIGHT-ALIGNED CELLS IN COLUMNS TOO NARROW TO HOLD THEM:
 *      1. the Downloads table ladders each file link over three lines, ragged
 *         against the right edge;
 *      2. the hand-delivery description in the totals block — a 40-word
 *         paragraph — is right-aligned in a column roughly 200px wide and
 *         reads as a column of fragments.
 *
 * ⭐ THE DOWNLOADS TABLE IS NOT HIDDEN, AND THE BRIEF ALLOWED HIDING IT. It
 *    carries two real, useful things: the Adventure Activity Book PDF and the
 *    printable Vocabulary Card Activity, each a live download link. Read out
 *    of the render itself, not assumed. Hiding it would remove the only copy
 *    of those links from the message.
 *
 * ⛔ AND THE COLUMN HEADINGS ARE NOT HIDDEN EITHER. The usual email trick is
 *    `thead { display: none }` plus a full block stack, but "Never" with no
 *    "Expires" above it is a word with no meaning, and this build has no way
 *    to re-label a stacked cell that survives Gmail. Left-aligning and letting
 *    the text wrap fixes the reading without deleting the labels.
 *
 * ⚠ SCOPE, STATED PLAINLY: `.email-order-details` is the class WooCommerce
 *   puts on the downloads table, the line-items table AND the totals table,
 *   and it is the same class in EVERY transactional email in this store. This
 *   block therefore changes the ≤480px rendering of all of them. That is
 *   deliberate and follows the round-10 ruling on the charset — the shared
 *   mechanism stays global rather than being forked per email. ⛔ It is
 *   presentation only: no cell, no value and no order of columns changes, and
 *   in Outlook desktop, which ignores `@media`, nothing changes at all.
 *
 * ⚠ NOT VERIFIED: no render was produced from this build. The failure was
 *   observed; the fix is stated as designed behaviour until Gandalf re-renders.
 */
@media only screen and (max-width: 480px) {
	#body_content_inner table.email-order-details th,
	#body_content_inner table.email-order-details td {
		padding: 8px 4px !important;
		font-size: 14px !important;
		line-height: 1.45 !important;
		word-break: break-word !important;
	}

	#body_content_inner table.email-order-details th.text-align-right,
	#body_content_inner table.email-order-details td.text-align-right,
	#body_content_inner table.email-order-details tfoot th,
	#body_content_inner table.email-order-details tfoot td {
		text-align: left !important;
	}

	#body_content_inner table.email-order-details img {
		max-width: 40px !important;
		height: auto !important;
	}
}
';

	/*
	 * ⭐ 1.19.370 · THE REVIEW-ASK STAR ROW'S HOVER RULES. Appended here, and
	 *    not in a second `woocommerce_email_styles` callback, because
	 *    WooCommerce assembles ONE stylesheet and a second callback would only
	 *    be a second place to look. The selectors are namespaced `.bhp-star`
	 *    and match nothing in any other email in this store.
	 *
	 * ⛔ THE RULES ARE UNCONDITIONAL RATHER THAN SCOPED TO ONE EMAIL ID,
	 *    because `woocommerce_email_styles` receives no `$email` in every
	 *    WooCommerce build this store has run on, and a scoping test that
	 *    silently never fires is worse than a few unused bytes.
	 */
	if ( function_exists( 'bhp_review_ask_star_css' ) ) {
		$css .= bhp_review_ask_star_css();
	}

	return $css;
}
add_filter( 'woocommerce_email_styles', 'bhp_email_brand_styles', 20 );

/* -------------------------------------------------------------------------
 * ⭐⭐ 1.19.372 · THE EMPTY H1 BAND, REMOVED RATHER THAN PADDED
 * ---------------------------------------------------------------------- */

/**
 * Drop the header band when the email has no heading to put in it.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ WHAT WAS ACTUALLY THERE, OBSERVED NOT ASSUMED.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ In `rs370-touch1.html`, `rs-touch1.html`, `rs-touch2.html` and
 *    `rs-web1.html` (all read at this desk 2026-09-05, line 30 of each) the
 *    review-ask emails render
 *    `<td id="header_wrapper" style="padding: 20px 32px 0; ..."><h1 ...></h1></td>`
 *    — an H1 with NO TEXT IN IT. From 1.19.372 the day-0 visit email joins
 *    them, by `bhp_visit_email_suppress_heading()`.
 *
 * ⭐ THE EMPTY `<h1>` ITSELF IS ZERO PIXELS TALL — `margin: 0`, no content,
 *    no line box. ⛔ SO REMOVING THE ELEMENT ALONE WOULD HAVE CHANGED NOTHING
 *    VISIBLE AND THE BRIEF WOULD HAVE BEEN "DONE" WITHOUT BEING DONE. What is
 *    actually visible is the wrapper cell's 20px of top padding above a hero
 *    photograph that already opens the message. Both are removed here.
 *
 * ⛔ WHY THIS IS A FILTER ON THE ASSEMBLED MESSAGE AND NOT A TEMPLATE
 *    OVERRIDE. Overriding `emails/email-header.php` is prohibited in this
 *    theme — the rule is stated in `woocommerce/emails/bhp-review-ask.php` and
 *    is the reason the heroes render inside the body rather than above the H1.
 *    `woocommerce_mail_content` runs on the finished, inlined HTML of every
 *    WooCommerce email and needs no override.
 *
 * ⛔ IT CANNOT AFFECT AN EMAIL THAT HAS A HEADING. The pattern requires the
 *    H1 to contain nothing but whitespace. Every ordinary transactional email
 *    in this store has a heading, so for those this filter matches nothing and
 *    returns the string it was handed, byte for byte.
 *
 * ⚠ NOT RUN: there is no PHP on this machine. The regexes are reasoned about
 *   and were written against four rendered documents that are on disk; they
 *   were NOT executed. Gandalf's staging run is the first execution.
 *
 * @since 1.19.372
 * @param string $content Assembled, style-inlined email HTML.
 * @return string
 */
function bhp_email_strip_empty_heading( $content ) {
	if ( ! is_string( $content ) || '' === $content ) {
		return $content;
	}

	// ⛔ Cheap guard first: no header wrapper, nothing to do.
	if ( false === strpos( $content, 'id="header_wrapper"' ) ) {
		return $content;
	}

	/*
	 * ⛔ THE H1 MUST BE EMPTY TO MATCH. `[^>]*` cannot cross the closing angle
	 *    bracket of the opening tag, and `\s*` between the tags means only
	 *    whitespace may sit inside. An H1 with a single character in it does
	 *    not match and the whole function becomes a no-op for that email.
	 */
	$stripped = preg_replace( '#<h1\b[^>]*>\s*</h1>#i', '', $content, 1, $count );

	if ( null === $stripped || ! $count ) {
		return $content;
	}

	/*
	 * ⭐ AND THEN THE PADDING, WHICH IS THE PART THAT IS ACTUALLY VISIBLE.
	 *    Only the `padding` declaration inside the `header_wrapper` cell's own
	 *    style attribute is rewritten; the rest of the attribute, and every
	 *    other element in the document, is untouched.
	 */
	$padded = preg_replace(
		'#(<td\b[^>]*\bid="header_wrapper"[^>]*\bstyle=")([^"]*)(")#i',
		'${1}padding: 0;${3}',
		$stripped,
		1,
		$padding_count
	);

	/*
	 * ⚠ ATTRIBUTE ORDER IS NOT GUARANTEED. Every rendered document on disk has
	 *   `id` before `style`, but Emogrifier is free to emit them the other way
	 *   round, so the reverse order is tried too. If NEITHER matches the H1 is
	 *   still gone and the only cost is 20px of cream — a smaller failure than
	 *   a regex that guesses.
	 */
	if ( null !== $padded && $padding_count ) {
		return $padded;
	}

	$padded = preg_replace(
		'#(<td\b[^>]*\bstyle=")([^"]*)("[^>]*\bid="header_wrapper")#i',
		'${1}padding: 0;${3}',
		$stripped,
		1
	);

	return ( null === $padded ) ? $stripped : $padded;
}
add_filter( 'woocommerce_mail_content', 'bhp_email_strip_empty_heading', 20 );

/* -------------------------------------------------------------------------
 * ⭐⭐ 1.19.370 · THE CHARSET ON EVERY WOOCOMMERCE EMAIL
 * ---------------------------------------------------------------------- */

/**
 * Add `charset=UTF-8` to the `Content-Type` header of every WooCommerce email.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THIS IS A FIX FOR AN OBSERVED DEFECT, NOT A PRECAUTION.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ WHAT WAS SEEN. FluentSMTP's own delivery log recorded the outgoing
 *    `Content-Type` as `text/html` with NO charset parameter, and the U+2605
 *    stars in the delivered 1.19.369 review ask arrived as `âââââ` —
 *    the exact signature of UTF-8 bytes decoded as CP1252 (U+2605 is
 *    `E2 98 85`, which is `â`, `˜`, `…` in Windows-1252). ⚠ Reported by
 *    Gandalf from the staging send; NOT re-observed in this build, which has
 *    no PHP runtime and sent nothing.
 *
 * ⛔ WHY WORDPRESS DID NOT ALREADY HANDLE IT. `wp_mail()` reads the charset
 *    out of the `Content-Type` header and otherwise falls back to
 *    `get_bloginfo( 'charset' )` — but FluentSMTP REPLACES `wp_mail()`
 *    wholesale. A header that states its charset explicitly does not depend on
 *    any mailer's fallback being the one we want.
 *
 * ⭐ WHY THIS ONE FILTER COVERS EVERYTHING THE BRIEF NAMES. `WC_Email::
 *    get_headers()` runs `apply_filters( 'woocommerce_email_headers', $header,
 *    $this->id, $this->object, $this )` for EVERY WooCommerce email. The
 *    review ask is a `WC_Email`; so is the visit day-0 email, which is
 *    `customer_completed_order` with the school-visit body fork. One filter,
 *    both emails, plus every receipt this store already sends — the mojibake
 *    defect was never specific to the star row.
 *
 * ⛔ IT ONLY ADDS. If a `charset` is already stated it is left exactly as it
 *    is, and the media type itself is never rewritten: an email configured as
 *    `multipart/alternative` or `text/plain` keeps that type and simply gains
 *    the parameter. Rewriting a media type from a header filter is how a
 *    plain-text email starts arriving as HTML source.
 *
 * @since 1.19.370
 * @param string $headers Existing headers, CRLF separated.
 * @return string
 */
function bhp_email_force_charset( $headers ) {
	if ( ! is_string( $headers ) || '' === $headers ) {
		return $headers;
	}

	$charset = get_bloginfo( 'charset' );
	$charset = ( is_string( $charset ) && '' !== trim( $charset ) ) ? trim( $charset ) : 'UTF-8';

	/*
	 * ⚠ LINE BY LINE, CASE-INSENSITIVELY, AND ONLY THE `Content-Type` LINE.
	 *   A naive `str_replace( 'text/html', ... )` would also rewrite the media
	 *   type mentioned inside a `List-Unsubscribe` URL or any future header
	 *   that happens to contain the string.
	 */
	$lines = preg_split( "/\r\n|\r|\n/", $headers );
	$out   = array();

	foreach ( (array) $lines as $line ) {
		if ( preg_match( '/^\s*content-type\s*:/i', (string) $line ) && ! preg_match( '/charset\s*=/i', (string) $line ) ) {
			$line = rtrim( (string) $line, "; \t" ) . '; charset=' . $charset;
		}

		$out[] = $line;
	}

	return implode( "\r\n", $out );
}
add_filter( 'woocommerce_email_headers', 'bhp_email_force_charset', 20 );

/* -------------------------------------------------------------------------
 * ⭐⭐ 1.19.371 · THE CHARSET ON THE MAILER OBJECT, NOT ONLY IN THE HEADER
 * ---------------------------------------------------------------------- */

/**
 * Force UTF-8 (and a transfer encoding that survives a 7-bit hop) on the
 * PHPMailer instance itself, for every message this site sends.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.370 DID NOT FINISH THE JOB, AND THE LOG SAYS SO.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ WHAT WAS SEEN AFTER 1.19.370. FluentSMTP's delivery log STILL recorded
 *    the content type as `text/html` with no charset for both staging test
 *    sends (log ids 6 and 7, reported by Gandalf 2026-09-05). ⚠ RELAYED, not
 *    observed at this desk — this build has no PHP runtime and sent nothing.
 *
 * ⭐ WHY THE HEADER FILTER ALONE CAN LOSE. `bhp_email_force_charset()` puts
 *    the parameter into the header STRING that is handed to `wp_mail()`.
 *    FluentSMTP replaces `wp_mail()` wholesale: it PARSES that string into its
 *    own structures and then re-emits headers from the PHPMailer object and
 *    its own settings. Anything it does not carry across the parse is lost.
 *    `$phpmailer->CharSet` is not a header to be parsed — it is the property
 *    PHPMailer and every mailer built on it uses to BUILD the `Content-Type`
 *    line, so setting it is the belt to the header filter's braces. ⛔ Both
 *    are kept. Neither is trusted alone.
 *
 * ⚠ ENCODING, AND WHY IT IS NOT LEFT AT THE DEFAULT. PHPMailer defaults to
 *   `8bit`. Raw 8-bit bytes are only safe if every hop advertises 8BITMIME; a
 *   relay that does not may strip the high bit or re-encode, which is a second
 *   independent way to turn `★` (`E2 98 85`) into mojibake. `quoted-printable`
 *   encodes those bytes as `=E2=98=85` — 7-bit clean end to end, and unlike
 *   base64 the ASCII body stays human-readable in a raw source view, which is
 *   exactly what Gimli is reading right now.
 *
 * ⛔ IT ONLY REPLACES THE DEFAULT. If some other plugin has deliberately set
 *    an encoding other than `8bit` (or the empty string), that choice is left
 *    alone. This function never downgrades a considered decision.
 *
 * ⛔ SCOPE, STATED PLAINLY. This is not limited to the review ask and the
 *    visit email. It cannot cleanly be: by `phpmailer_init` the message is a
 *    mailer object with no reliable back-reference to the `WC_Email` that
 *    built it, and sniffing the subject to decide a charset would be a worse
 *    engineering decision than applying the site's own charset to the site's
 *    own mail. The site charset is UTF-8; every email it sends should say so.
 *    ⚠ `bhp_email_phpmailer_charset_enabled` is the one-line off switch if
 *    that judgement is ever wrong.
 *
 * @since 1.19.371
 * @param PHPMailer\PHPMailer\PHPMailer|mixed $phpmailer Mailer instance.
 * @return void
 */
function bhp_email_phpmailer_charset( $phpmailer ) {
	if ( ! is_object( $phpmailer ) ) {
		return;
	}

	/**
	 * Filter whether the mailer-level charset is forced.
	 *
	 * @since 1.19.371
	 * @param bool  $enabled   Default true.
	 * @param mixed $phpmailer Mailer instance.
	 */
	if ( ! apply_filters( 'bhp_email_phpmailer_charset_enabled', true, $phpmailer ) ) {
		return;
	}

	$charset = get_bloginfo( 'charset' );
	$charset = ( is_string( $charset ) && '' !== trim( $charset ) ) ? trim( $charset ) : 'UTF-8';

	$phpmailer->CharSet = $charset; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCase

	/*
	 * ⚠ ONLY THE DEFAULT IS REPLACED. See the docblock. `isset()` is not used
	 *   because the property always exists on a PHPMailer instance; the test
	 *   is on its VALUE.
	 */
	$current = isset( $phpmailer->Encoding ) ? strtolower( trim( (string) $phpmailer->Encoding ) ) : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCase

	if ( '' === $current || '8bit' === $current ) {
		$phpmailer->Encoding = 'quoted-printable'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCase
	}
}
add_action( 'phpmailer_init', 'bhp_email_phpmailer_charset', 99 );

/**
 * The `Content-Type` header line every BHP-originated `wp_mail()` call passes.
 *
 * ⭐ ONE STRING, ONE PLACE. The review-ask CLI test send already stated the
 *    charset inline; this makes that statement a shared, testable fact rather
 *    than a literal that a future editor can drop from one call site without
 *    anything noticing. ⛔ It is a helper, not a filter — nothing is changed
 *    for callers that do not use it.
 *
 * @since 1.19.371
 * @return string e.g. `Content-Type: text/html; charset=UTF-8`
 */
function bhp_email_html_content_type_header() {
	$charset = get_bloginfo( 'charset' );
	$charset = ( is_string( $charset ) && '' !== trim( $charset ) ) ? trim( $charset ) : 'UTF-8';

	return 'Content-Type: text/html; charset=' . $charset;
}

