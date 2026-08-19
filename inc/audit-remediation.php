<?php
/**
 * Audit Remediation (2026-07-18) — small, isolated, reversible fixes from the
 * Fable production audit. Grouped in one include so the whole remediation set
 * can be reviewed and, if ever needed, disabled by removing a single require.
 *
 * Contents:
 * 1. Legacy author-slug 301 redirect (Finding #2) — the founder's author
 *    archive nicename was changed from the email-derived
 *    "andrewbraveheartspublishing-com" to the clean "andrew-signore"; this
 *    301s the old URL so no inbound link or crawl equity is lost. The
 *    display-name / nicename change itself is WordPress user data applied per
 *    environment via WP-CLI, not theme code — this file only handles the
 *    redirect.
 * 2. Hide the native WooCommerce Reviews tab when a product has zero native
 *    reviews (Finding #8) — the empty "Reviews (0)" tab sat next to strong
 *    external (Kirkus/Amazon) review evidence and read as a negative. The tab
 *    returns automatically the moment a real native review is approved; the
 *    external review components are untouched.
 * 3. Relabel the product-meta "SKU:" as "ISBN:" on single book product pages
 *    (Finding #9) — the displayed value IS each book's ISBN. This is a display
 *    label only (scoped gettext on product pages); the underlying WooCommerce
 *    SKU field / fulfillment identifier is never altered.
 */

defined( 'ABSPATH' ) || exit;

/**
 * 301 the legacy author archive slug to the current one.
 *
 * Runs early on template_redirect and matches the raw request path (not
 * is_author(), which is false once the old nicename no longer resolves to a
 * user). Scoped to the exact legacy slug only — no other URL is affected.
 */
add_action( 'template_redirect', 'bhp_redirect_legacy_author_slug', 1 );
function bhp_redirect_legacy_author_slug() {
	$req = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	if ( '' === $req ) {
		return;
	}
	if ( preg_match( '#^/author/andrewbraveheartspublishing-com/?(\?.*)?$#i', $req ) ) {
		wp_safe_redirect( home_url( '/author/andrew-signore/' ), 301 );
		exit;
	}
}

/**
 * Finding #8: hide the native Reviews tab on products that have no native
 * WooCommerce reviews. Leaves it fully intact (and re-enabled automatically)
 * for any product that later gains a real native review, and never touches the
 * theme's external Kirkus/Amazon review components.
 */
add_filter( 'woocommerce_product_tabs', 'bhp_hide_empty_reviews_tab', 98 );
function bhp_hide_empty_reviews_tab( $tabs ) {
	if ( isset( $tabs['reviews'] ) ) {
		global $product;
		$p = $product instanceof WC_Product ? $product : ( function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null );
		if ( $p instanceof WC_Product && (int) $p->get_review_count() === 0 ) {
			unset( $tabs['reviews'] );
		}
	}
	return $tabs;
}

/**
 * Finding #9: display "ISBN:" instead of "SKU:" in the product meta on single
 * book product pages. Display label only — the WooCommerce SKU field and the
 * Bookvault fulfillment identifier are unchanged. Scoped tightly: only the
 * exact WooCommerce "SKU:" string, only on a single product page, and the
 * cheap string compare runs before the is_product() context check.
 */
add_filter( 'gettext', 'bhp_relabel_sku_as_isbn', 10, 3 );
function bhp_relabel_sku_as_isbn( $translated, $text, $domain ) {
	if ( 'woocommerce' === $domain && 'SKU:' === $text && function_exists( 'is_product' ) && is_product() ) {
		return __( 'ISBN:', 'brave-hearts' );
	}
	return $translated;
}

/**
 * Findings #6 / BH-02 / BH-03 — Mariana Trench paperback single-variation UX.
 *
 * The Mariana paperback stays a VARIABLE product; variation 334 (its SKU/ISBN
 * 9798234014016, Bookvault mapping, bundle-pricing catalog key, price, analytics
 * identity, and 4 historical prod orders) is preserved EXACTLY. Two pieces make
 * it behave like a simple product for the shopper without changing any product
 * data:
 *
 * 1. Auto-select of the sole variation is handled solely by
 *    assets/js/product-format-autoselect.js, enqueued in functions.php with a
 *    `defer` strategy so it runs AFTER WooCommerce's (also-deferred)
 *    wc-add-to-cart-variation form is initialized — deterministic, no race.
 *    (BH-02: the previous `bhp_single_variation_ux` inline script was a SECOND,
 *    redundant auto-select that ran during page parse, before that deferred
 *    form was wired — a genuine timing hazard. It has been removed; the CSS that
 *    hides the one-option selector now lives in style.css keyed to `.postid-333`
 *    for a zero-flash hide.)
 *
 * 2. BH-03: the internal binding term "Perfect Bound" (and its hardcover
 *    counterpart "Case Bound") is suppressed from every customer-facing cart and
 *    order surface below. This is DISPLAY-ONLY — the underlying variation
 *    attribute, order item meta, and fulfillment data are untouched, so Bookvault
 *    still receives the correct variation. The product name already carries the
 *    format ("… (Paperback)"), so the redundant "Paperback: Perfect Bound" row is
 *    simply removed rather than relabeled.
 */
function bhp_is_binding_jargon( $value ) {
	$value = wp_strip_all_tags( (string) $value );
	return ( false !== stripos( $value, 'Perfect Bound' ) || false !== stripos( $value, 'Case Bound' ) );
}

/**
 * BH-03: normalize the binding-jargon variation VALUE to the plain format word
 * at DISPLAY time — "Perfect Bound" → "Paperback", "Case Bound" → "Hardcover".
 * This is the one filter WooCommerce applies to the variation option value on
 * every Store-API surface (the WooCommerce Blocks cart/checkout and the bundle
 * plugin's custom drawer, which reads `item.variation[0].value` as its format
 * label). It changes only what renders — the stored variation 334 attribute,
 * order item meta, SKU, and Bookvault mapping are all untouched. The Blocks
 * cart/checkout then hide the now-redundant "Paperback: Paperback" meta row via
 * CSS (see style.css BH-03), while the drawer keeps a clean "Paperback" label.
 */
add_filter( 'woocommerce_variation_option_name', 'bhp_normalize_binding_label', 10, 1 );
function bhp_normalize_binding_label( $value ) {
	if ( false !== stripos( (string) $value, 'Perfect Bound' ) ) {
		return __( 'Paperback', 'brave-hearts' );
	}
	if ( false !== stripos( (string) $value, 'Case Bound' ) ) {
		return __( 'Hardcover', 'brave-hearts' );
	}
	return $value;
}

// Cart drawer, cart page, and checkout order-summary line-item meta.
add_filter( 'woocommerce_get_item_data', 'bhp_hide_binding_jargon_cart', 10, 2 );
function bhp_hide_binding_jargon_cart( $item_data, $cart_item ) {
	if ( ! is_array( $item_data ) ) {
		return $item_data;
	}
	foreach ( $item_data as $i => $data ) {
		if ( isset( $data['value'] ) && bhp_is_binding_jargon( $data['value'] ) ) {
			unset( $item_data[ $i ] );
		}
	}
	return array_values( $item_data );
}

// Order-received page, order emails, and My Account order view.
add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'bhp_hide_binding_jargon_order', 10, 2 );
function bhp_hide_binding_jargon_order( $formatted_meta, $order_item ) {
	if ( ! is_array( $formatted_meta ) ) {
		return $formatted_meta;
	}
	foreach ( $formatted_meta as $key => $meta ) {
		if ( isset( $meta->display_value ) && bhp_is_binding_jargon( $meta->display_value ) ) {
			unset( $formatted_meta[ $key ] );
		}
	}
	return $formatted_meta;
}

/**
 * Finding #5: compact "Complete Collection" upgrade module on individual
 * product pages. Positioned on woocommerce_after_single_product_summary at
 * priority 15 — after the purchase area + the theme's trust/fulfillment
 * sections (which render on woocommerce_single_product_summary and
 * after_single_product_summary priority 5), and before WooCommerce's Related
 * Products (default priority 20). Format-aware (paperback vs hardcover),
 * reads every price from the bundle-pricing plugin's canonical source
 * (bhp_bundle_expected_price / _rules — never a second hardcoded figure), and
 * links to the proven /complete-collection/ page rather than re-implementing
 * add-to-cart. Skips gracefully if the plugin is inactive or the product is
 * not one of the six catalog editions.
 */
add_action( 'woocommerce_after_single_product_summary', 'bhp_product_collection_upsell', 15 );
function bhp_product_collection_upsell() {
	if ( ! function_exists( 'bhp_bundle_catalog' ) || ! function_exists( 'bhp_bundle_expected_price' ) || ! function_exists( 'bhp_bundle_rules' ) ) {
		return;
	}
	global $product;
	$p = ( $product instanceof WC_Product ) ? $product : ( function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null );
	if ( ! $p instanceof WC_Product ) {
		return;
	}
	$pid     = (int) $p->get_id();
	$catalog = bhp_bundle_catalog();
	$format  = null;
	foreach ( $catalog as $fmt => $titles ) {
		foreach ( $titles as $info ) {
			if ( (int) $info['product_id'] === $pid ) {
				$format = $fmt;
				break 2;
			}
		}
	}
	if ( null === $format || ! isset( $catalog[ $format ] ) ) {
		return;
	}

	/*
	 * ⛔ CYCLE144-LD-23 — THE PRODUCT ID IS NOT THE FORMAT THE VISITOR IS LOOKING AT.
	 *
	 * OBSERVED LIVE on staging, not inferred: the hardcover permalink
	 * /product/…-the-mariana-trench-hardcover/ 301s to the canonical PAPERBACK
	 * product page carrying `?bhp_format=hardcover`. `global $product` is
	 * therefore the paperback on a page the customer is reading as HARDCOVER,
	 * and the loop above resolved 'paperback'.
	 *
	 * This mismatch is PRE-EXISTING — the module has always labelled that page
	 * "See the Complete Paperback Collection". While the CTA was a link it was
	 * a wrong word. Now that the CTA ADDS TO THE CART it would put three
	 * paperbacks in the basket of someone shopping for hardcovers, which is a
	 * different order at a different price. Making the CTA direct is what
	 * turned a cosmetic defect into a commercial one, so it is fixed here
	 * rather than filed.
	 *
	 * `bhp_book_incoming_format()` is the theme's ONE resolver for this
	 * question (inc/book-formats.php) and the same one the format selector on
	 * this very page reads: the `?bhp_format` parameter wins, then the viewed
	 * product, then the site default. Reusing it is what stops this card and
	 * the selector directly above it from ever disagreeing again.
	 *
	 * It can also return 'kindle' or 'collection', neither of which is a
	 * bundle catalog key — those fall back to the product-derived format
	 * rather than fataling on a missing array key.
	 */
	if ( function_exists( 'bhp_book_incoming_format' ) ) {
		$selected = bhp_book_incoming_format();
		if ( in_array( $selected, array( 'paperback', 'hardcover' ), true ) && isset( $catalog[ $selected ] ) ) {
			$format = $selected;
		}
	}

	$rules      = bhp_bundle_rules( $format );
	if ( empty( $rules[3] ) ) {
		return;
	}
	$unit       = (float) bhp_bundle_expected_price( $format );
	$combined   = 3 * $unit;
	$discount   = (float) $rules[3]['discount'];
	$collection = $combined - $discount;
	$is_pb      = ( 'paperback' === $format );
	$heading    = $is_pb ? __( 'Get all three paperback adventures', 'brave-hearts' ) : __( 'Build the complete hardcover library', 'brave-hearts' );
	/*
	 * 2026-08-05 — Andrew: "WE HAVE TO SIMPLIFY THE PURCHASE PATH FOR ALL CTA
	 * buttons". This control used to read "See the Complete ___ Collection" and
	 * link to /complete-collection/. It now ADDS the three books and lands on
	 * /checkout/, so the verb had to change with the behaviour: a button
	 * labelled "See" that charges you is worse than the extra page it saves.
	 *
	 * ⛔ THE NEW LABEL IS NOT NEW COPY. "Add the Complete Hardcover Collection"
	 *    and "Add the Complete Paperback Collection" are the already-approved,
	 *    already-live strings from bhp_bundle_landing_format_copy() in
	 *    plugins/brave-hearts-bundle-pricing/includes/bundle-landing-page.php,
	 *    reused verbatim rather than written here.
	 *
	 * This module is the right place for the direct path and the site's blog
	 * CTAs are not: here the FORMAT IS KNOWN (it is derived from the product
	 * being viewed, above) and the price is on screen. A blog reader has chosen
	 * neither, so BHP_CTA_Engine's collection CTAs stay informational links.
	 */
	$cta_label  = $is_pb ? __( 'Add the Complete Paperback Collection', 'brave-hearts' ) : __( 'Add the Complete Hardcover Collection', 'brave-hearts' );
	$cc_url     = home_url( '/complete-collection/' );

	$titles = array();
	foreach ( $catalog[ $format ] as $info ) {
		// Show the short adventure name, not the full "Adventures of..." label.
		$label    = $info['label'];
		$titles[] = ( false !== strpos( $label, ':' ) ) ? trim( substr( $label, strrpos( $label, ':' ) + 1 ) ) : $label;
	}

	// Scoped CSS, printed once per request.
	static $printed_css = false;
	if ( ! $printed_css ) {
		$printed_css = true;
		echo '<style id="bhp-cc-upsell-css">'
			. '.bhp-cc-upsell{--bhp-cc-green:var(--color-forest,#173f2f);--bhp-cc-gold:var(--color-gold,#D9A45F);--bhp-cc-cream:#f7f2e7;max-width:720px;margin:2.5rem auto;padding:1.5rem 1.75rem;background:var(--bhp-cc-cream);border:1px solid rgba(23,63,47,.18);border-radius:12px;box-shadow:0 2px 10px rgba(23,63,47,.06);}'
			. '.bhp-cc-upsell__badge{display:inline-block;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;font-weight:700;color:var(--bhp-cc-green);background:rgba(217,164,95,.22);padding:.25rem .6rem;border-radius:999px;margin-bottom:.6rem;}'
			. '.bhp-cc-upsell__title{margin:.1rem 0 .6rem;font-size:1.3rem;line-height:1.25;color:var(--bhp-cc-green);}'
			. '.bhp-cc-upsell__list{list-style:none;margin:0 0 .9rem;padding:0;display:flex;flex-wrap:wrap;gap:.35rem .9rem;}'
			. '.bhp-cc-upsell__list li{position:relative;padding-left:1.15rem;font-size:.95rem;color:#2a2a24;}'
			. '.bhp-cc-upsell__list li::before{content:"\2713";position:absolute;left:0;color:var(--bhp-cc-green);font-weight:700;}'
			. '.bhp-cc-upsell__prices{display:flex;flex-wrap:wrap;align-items:baseline;gap:.4rem .9rem;margin:.2rem 0 1rem;}'
			. '.bhp-cc-upsell__strike{color:#706456;text-decoration:line-through;font-size:.95rem;}'
			. '.bhp-cc-upsell__price{font-size:1.5rem;font-weight:700;color:var(--bhp-cc-green);}'
			. '.bhp-cc-upsell__save{font-size:.85rem;font-weight:700;color:#805800;background:rgba(217,164,95,.28);padding:.15rem .5rem;border-radius:6px;}'
			. '.bhp-cc-upsell__ship{width:100%;font-size:.85rem;color:#5a5a50;margin:0;}'
			. '.bhp-cc-upsell__cta{display:inline-block;background:var(--bhp-cc-green);color:#fff;font-weight:600;padding:.7rem 1.3rem;border-radius:8px;text-decoration:none;border:2px solid var(--bhp-cc-green);transition:background .15s,color .15s;}'
			. '.bhp-cc-upsell__cta:hover,.bhp-cc-upsell__cta:focus{background:#fff;color:var(--bhp-cc-green);}'
			/*
			 * 2026-08-05 — the CTA became a <button type="submit">. style.css's
			 * base button rule matches `button[type='submit']` by ATTRIBUTE
			 * (specificity 0,1,1), which outranks the plain `.bhp-cc-upsell__cta`
			 * class rule above (0,1,0) and would silently repaint this control
			 * as a 48px-tall uppercase site button. `button.bhp-cc-upsell__cta`
			 * ties the specificity at 0,1,1 and this stylesheet is printed later
			 * in the document, so it wins. The rendered control is visually
			 * identical to the anchor it replaced.
			 */
			. 'form.bhp-cc-upsell__form{margin:0;padding:0;border:0;}'
			. 'button.bhp-cc-upsell__cta{font:inherit;font-weight:600;text-transform:none;letter-spacing:normal;min-height:0;line-height:normal;cursor:pointer;}'
			. '.bhp-cc-upsell__note{display:block;margin-top:.55rem;font-size:.8rem;color:#6a6a60;}'
			. '@media(max-width:480px){.bhp-cc-upsell{margin:1.75rem auto;padding:1.25rem;}.bhp-cc-upsell__title{font-size:1.15rem;}.bhp-cc-upsell__cta{display:block;text-align:center;}}'
			. '</style>';
	}

	printf(
		'<section class="bhp-cc-upsell" aria-labelledby="bhp-cc-upsell-title">'
			. '<span class="bhp-cc-upsell__badge">%1$s</span>'
			. '<h2 id="bhp-cc-upsell-title" class="bhp-cc-upsell__title">%2$s</h2>'
			. '<ul class="bhp-cc-upsell__list">%3$s</ul>'
			. '<div class="bhp-cc-upsell__prices">'
				. '<span class="bhp-cc-upsell__strike">%4$s</span>'
				. '<span class="bhp-cc-upsell__price">%5$s</span>'
				. '<span class="bhp-cc-upsell__save">%6$s</span>'
				. '<p class="bhp-cc-upsell__ship">%7$s</p>'
			. '</div>'
			. '%8$s'
			. '<span class="bhp-cc-upsell__note">%9$s</span>'
		. '</section>',
		esc_html__( 'Best Value', 'brave-hearts' ),
		esc_html( $heading ),
		implode( '', array_map( function ( $t ) { return '<li>' . esc_html( $t ) . '</li>'; }, $titles ) ),
		esc_html( '$' . number_format( $combined, 2 ) . ' if bought separately' ),
		esc_html( '$' . number_format( $collection, 2 ) . ' collection' ),
		esc_html( sprintf( __( 'Save $%s', 'brave-hearts' ), number_format( $discount, 2 ) ) ),
		esc_html__( 'All three adventures in one order, shipped together.', 'brave-hearts' ),
		/*
		 * Adds the three real books of THIS product's format and finishes on
		 * /checkout/. Falls closed to the original <a href="/complete-collection/">
		 * — same label, same classes, same three analytics attributes — whenever
		 * the bundle plugin is not available, which is also the only state in
		 * which this whole module is skipped anyway (see the guard at the top).
		 */
		function_exists( 'bhp_collection_add_to_cart_cta' )
			? bhp_collection_add_to_cart_cta(
				array(
					'format'     => $format,
					'label'      => $cta_label,
					'class'      => 'bhp-cc-upsell__cta',
					'form_class' => 'bhp-cc-upsell__form',
					'event'      => 'collection_upsell_click',
					'source'     => 'product_page',
					'extra'      => 'data-bhp-format="' . esc_attr( $format ) . '"',
				)
			)
			: sprintf(
				'<a class="bhp-cc-upsell__cta" href="%1$s" data-bhp-event="collection_upsell_click" data-bhp-format="%2$s" data-bhp-source="product_page">%3$s</a>',
				esc_url( $cc_url ),
				esc_attr( $format ),
				esc_html( $cta_label )
			),
		esc_html__( 'This individual book is a great start - the collection just saves you more.', 'brave-hearts' )
	);
}

/**
 * Finding #27: native, provider-free contact-form submission.
 *
 * The theme's contact form (template-parts/contact/contact-form.php) was built
 * provider-neutral but no external provider is configured on either
 * environment, so the page was silently falling back to a bare mailto: link.
 * This turns the existing on-site form into a real server-side submission with
 * **no heavy form platform**: an admin-post endpoint, a WordPress nonce plus a
 * honeypot for spam, strict server-side validation, a SAFE server-controlled
 * recipient (never a user-supplied address), on-page success/error states, and
 * a submit analytics event. Activates only when nothing else set a form action,
 * so a future external provider still wins.
 */
const BHP_CONTACT_ACTION = 'bhp_contact_submit';

/** Default the contact form action to our own endpoint when nothing else set one. */
add_filter( 'bhp_contact_form_action', 'bhp_contact_default_native_action', 20 );
function bhp_contact_default_native_action( $action ) {
	$action = is_string( $action ) ? trim( $action ) : '';
	if ( '' !== $action ) {
		return $action; // an explicitly configured external provider always wins
	}
	return admin_url( 'admin-post.php' );
}

/** True when the contact form is posting to our native endpoint (not an external provider). */
function bhp_contact_is_native( $action ) {
	return untrailingslashit( (string) $action ) === untrailingslashit( admin_url( 'admin-post.php' ) );
}

add_action( 'admin_post_nopriv_' . BHP_CONTACT_ACTION, 'bhp_handle_contact_submit' );
add_action( 'admin_post_' . BHP_CONTACT_ACTION, 'bhp_handle_contact_submit' );
function bhp_handle_contact_submit() {
	// Keep every redirect on-site: only trust a source_page under our own host.
	$source = isset( $_POST['source_page'] ) ? esc_url_raw( wp_unslash( $_POST['source_page'] ) ) : '';
	if ( '' === $source || 0 !== strpos( $source, home_url() ) ) {
		$source = home_url( '/contact/' );
	}
	$redirect = function ( $status ) use ( $source ) {
		wp_safe_redirect( add_query_arg( 'bhp_contact', $status, $source ) . '#contact-form' );
		exit;
	};

	// Nonce.
	if ( ! isset( $_POST['bhp_contact_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['bhp_contact_nonce'] ), BHP_CONTACT_ACTION ) ) {
		$redirect( 'error' );
	}

	// Honeypot — real users never fill this. Report success so bots don't probe,
	// but send nothing.
	if ( ! empty( $_POST['bhp_contact_hp'] ) ) {
		$redirect( 'success' );
	}

	$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$org     = isset( $_POST['organization'] ) ? sanitize_text_field( wp_unslash( $_POST['organization'] ) ) : '';
	$role    = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
	$inquiry = isset( $_POST['inquiry_type'] ) ? sanitize_text_field( wp_unslash( $_POST['inquiry_type'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	// Server-side validation (mirrors the required attributes on the form).
	if ( '' === $name || '' === $inquiry || '' === $message || ! is_email( $email ) ) {
		$redirect( 'invalid' );
	}

	// SAFE recipient: server-controlled only, never from user input.
	$to = sanitize_email( apply_filters( 'bhp_contact_recipient', get_option( 'admin_email' ) ) );
	if ( ! is_email( $to ) ) {
		$to = 'andrew@braveheartspublishing.com';
	}

	$subject = sprintf( '[Brave Hearts Contact] %1$s - %2$s', $inquiry, $name );
	$body    = implode( "\n", array(
		'Name: ' . $name,
		'Email: ' . $email,
		'Organization: ' . ( '' !== $org ? $org : ' - ' ),
		'Role: ' . ( '' !== $role ? $role : ' - ' ),
		'Inquiry type: ' . $inquiry,
		'Source page: ' . $source,
		'',
		'Message:',
		$message,
	) );
	// Reply-To carries the visitor's address; the From stays on our own domain
	// so the message is not spoofing the sender.
	$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );

	$sent = wp_mail( $to, $subject, $body, $headers );

	$redirect( $sent ? 'success' : 'error' );
}

/**
 * BH-08 — tidy the empty express-checkout frame.
 *
 * WooCommerce Blocks + the Stripe gateway render the express-checkout container
 * AND its "Or continue below" divider even when no supported wallet button
 * (Apple Pay / Google Pay / Link) is available for the current browser/device —
 * leaving an empty bordered frame and an orphaned divider (reproduced in a clean
 * wallet-less browser). This collapses the express block and the divider ONLY
 * when no express button actually renders, and restores them the moment a real
 * wallet button appears. Standard card checkout is never touched, and nothing in
 * Stripe's payment-registration path is modified — express stays fully
 * functional for customers whose device HAS a supported wallet.
 *
 * ═════════════════════════════════════════════════════════════════════════════
 * ⛔ P2-1 (2026-08-03) — THE ONE-WAY LATCH THAT HID APPLE PAY AND GOOGLE PAY
 *    ON PRODUCTION FOR EVERY CUSTOMER WHO HAD THEM. Root-caused, reproduced
 *    and fixed here. Do not reintroduce the pattern below.
 * ═════════════════════════════════════════════════════════════════════════════
 *
 * THE DEFECT, in one sentence: the predicate measured descendants of the very
 * subtree it had just set to `display:none`.
 *
 *   1. First tick fires before Stripe's Express Checkout Element has mounted.
 *      No button is measurable yet, so `ex.style.display = 'none'`.
 *   2. `display:none` makes EVERY descendant rect exactly 0×0, permanently and
 *      by specification — not "small", zero.
 *   3. Every later tick, and every MutationObserver callback, re-ran the SAME
 *      measurement inside that now-hidden subtree, read 0×0 again, and took the
 *      hide branch again. **The re-show branch was unreachable from the moment
 *      the first hide ran.** The self-heal healed nothing; it re-asserted the
 *      failure 16 times.
 *
 * OBSERVED, not inferred (CYCLE142 ECE probe, production checkout, real iPhone
 * UA at 390×844, client-side-only intervention, nothing persisted):
 *
 *   before: inline `display: none`, computed `none`, container height 0,
 *           the two Stripe wallet iframes measuring {w:0, h:0, cssH:"0px"}
 *   after clearing that ONE inline style, and changing nothing else:
 *           the same two iframes measured {w:282, h:56}, container height 137,
 *           and BH-08's own predicate re-evaluated to TRUE.
 *
 * Stripe was never the problem. The Stripe side was green throughout:
 * `unverified_payment_methods_on_domain` empty, Apple/Google preferences
 * enabled, no killswitches, `.well-known` association 200. This code was the
 * problem.
 *
 * ─── THE THREE THINGS THE FIX CHANGES, AND WHY EACH IS LOAD-BEARING ─────────
 *
 * 1. ⭐ NEVER SUPPRESS RENDERING IN THE SUBTREE WE MEASURE — enforced
 *    structurally, not by discipline. The collapse is now `height:0` +
 *    `overflow:hidden` via a CSS class. The subtree keeps a real layout box,
 *    keeps painting, and keeps REAL, non-zero `getBoundingClientRect()`
 *    values; the clip is what removes it from view. The measurement is
 *    therefore truthful in BOTH states, which is precisely what makes the
 *    re-show branch reachable.
 *
 *    WHY CLIPPING RATHER THAN `visibility:hidden`, HONESTLY STATED. Both keep
 *    rects non-zero, so both satisfy the rule above at spec level. Clipping is
 *    chosen because it is the LEAST suppressive option that still collapses
 *    the space: the subtree keeps painting, so nothing inside it can be
 *    deferred, throttled or skipped by the browser or by Stripe on account of
 *    our hiding. When the whole defect was a hidden subtree misreporting
 *    itself, the smallest possible amount of hiding is the right instinct.
 *
 *    ⚠️ AN ATTEMPT TO PROVE `visibility:hidden` STRICTLY WORSE WAS RUN AND
 *       ITS RESULT IS NOT RELIED ON. A three-arm A/B (clip vs visibility vs
 *       display:none) appeared to show Stripe failing to size its iframes
 *       under `visibility:hidden`. It did not: the harness's injected loop
 *       never executed, so no arm's CSS was ever applied, and the only real
 *       variable was a Stripe/Link WARM-UP effect — the first checkout load in
 *       a fresh browser profile reports no wallet (ECE iframes 282×8) and
 *       every subsequent load reports one (282×56). Reversing the arm order
 *       moved the 8px result to whichever arm ran first. **No claim that
 *       `visibility:hidden` suppresses Stripe's sizing is made here, because
 *       it was not established.** Recorded rather than deleted so nobody
 *       re-derives the same wrong conclusion from the same confound.
 *
 *    ⭐ That warm-up effect is itself worth knowing before anyone QAs wallets
 *       again: a cold headless profile is NOT a wallet-less device, and a
 *       single first-load measurement showing no wallet proves nothing.
 *
 *    `position:absolute` + `opacity:0` was rejected separately: taking the
 *    block out of flow inside the Blocks checkout column risks it overlaying
 *    the payment methods on a narrow viewport. `height:0` + `overflow:hidden`
 *    collapses the space with no positioning-context surprises and keeps the
 *    ORIGINAL tidy intent intact: no big empty bordered box when the customer
 *    has no wallet.
 *
 *    Because the subtree is clipped rather than hidden, it is marked `inert`
 *    and `aria-hidden` while collapsed — otherwise a keyboard user could tab
 *    into a wallet button they cannot see. Both are removed the moment it is
 *    shown, and neither affects layout or painting, which is exactly why they
 *    are usable here and `visibility:hidden` is not.
 *
 * 2. ⭐ THE ONLY LATCH LEFT RUNS TOWARD SHOWING. `everHadWallet` is sticky:
 *    once a real wallet button has been measured even once, this code never
 *    collapses the block again for the life of the page. A wallet that
 *    flickers during a React re-render can therefore never be hidden by a
 *    momentary bad measurement. The failure mode of this function must never
 *    again be "wallet hidden", so the one irreversible state it can enter is
 *    the safe one.
 *
 * 3. A GRACE PERIOD. Nothing is collapsed for the first 2,500ms, so the
 *    pre-mount frames of a page where a wallet IS available never take the
 *    hide branch at all.
 *
 * 4. THE OBSERVER WATCHES ATTRIBUTES — and coalesces. Stripe sizes its wallet
 *    iframes by writing an inline `style` height on an EXISTING node, so the
 *    old `{childList:true, subtree:true}` observer was deaf to the single
 *    most important signal on the page. Watching attributes across the whole
 *    Blocks checkout means hundreds of callbacks during hydration, so the
 *    callback schedules at most one measurement per animation frame rather
 *    than forcing a synchronous layout per mutation.
 *
 * Also: any inline `display:none` left on the container by a cached copy of
 * the OLD script is actively cleared on first run, so a customer holding a
 * stale bundle is repaired rather than left latched.
 *
 * ROLLBACK: delete this whole block. Deleting it leaves express visible-always
 * (the pre-BH-08 state), which is cosmetically imperfect and commercially
 * safe — the failure mode of this function must never again be "wallet hidden".
 */
add_action( 'wp_enqueue_scripts', 'bhp_bh08_tidy_empty_express', 40 );
function bhp_bh08_tidy_empty_express() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}
	wp_register_style( 'bhp-express-tidy', false, array(), null );
	wp_enqueue_style( 'bhp-express-tidy' );
	/*
	 * Collapsed by CLIPPING, not by display:none and not by visibility:hidden.
	 * Every property here removes the empty frame from view and from the flow
	 * while KEEPING the subtree laid out, painted and measurable — which is
	 * what lets Stripe finish mounting and lets us notice when it does.
	 * `inert` + `aria-hidden` (set in JS) handle keyboard and screen-reader
	 * access; neither of them suppresses rendering.
	 */
	wp_add_inline_style( 'bhp-express-tidy', '.wc-block-components-express-payment.bhp-express-collapsed,'
		. '.wc-block-components-express-payment-continue-rule.bhp-express-collapsed{'
		. 'height:0!important;min-height:0!important;'
		. 'margin:0!important;padding:0!important;border:0!important;overflow:hidden!important}' );

	wp_register_script( 'bhp-express-tidy', false, array(), null, true );
	wp_enqueue_script( 'bhp-express-tidy' );
	$js = <<<JS
(function(){
	var CLS = 'bhp-express-collapsed';
	var GRACE_MS = 2500;
	var startedAt = Date.now();
	// Sticky, and deliberately one-way in the SAFE direction. See note 2.
	var everHadWallet = false;

	function measurable(el){
		// Real rect first. Inside the collapsed state this is still truthful,
		// because visibility:hidden preserves layout boxes.
		var r = el.getBoundingClientRect();
		if (r.width > 20 && r.height > 20) { return true; }
		// Belt and braces: Stripe writes the wallet height as an inline style
		// on the iframe. If a rect is momentarily unavailable (an ancestor
		// mid-reflow), the declared size is still a truthful availability
		// signal and is read directly rather than guessed at.
		var h = parseFloat((el.style && el.style.height) || el.getAttribute('height') || 0);
		var w = parseFloat((el.style && el.style.width) || el.getAttribute('width') || 0);
		return (h > 20 && w > 20);
	}

	function hasWalletButton(ex){
		// A real Apple Pay / Google Pay / Link button renders ~40-56px tall.
		// When no wallet is available Stripe still injects its express element
		// iframes, but they stay at ~8px (empty). Only count a control that is
		// actually laid out at a meaningful size.
		var area = ex.querySelector('.wc-block-components-express-payment__event-buttons') || ex;
		var els = area.querySelectorAll('button, [role="button"], iframe');
		for (var i = 0; i < els.length; i++) {
			if (measurable(els[i])) { return true; }
		}
		return false;
	}

	function setCollapsed(el, collapsed){
		if (!el) { return; }
		// Clear any inline display left behind by the pre-P2-1 script, so a
		// cached bundle cannot keep a customer latched into the hidden state.
		if (el.style && el.style.display === 'none') { el.style.removeProperty('display'); }
		if (el.classList.contains(CLS) === collapsed) { return; }
		if (collapsed) {
			el.classList.add(CLS);
			// The subtree is clipped, not hidden, so it is still focusable and
			// still in the accessibility tree unless we say otherwise.
			el.setAttribute('aria-hidden', 'true');
			try { el.inert = true; } catch (e) {}
		} else {
			el.classList.remove(CLS);
			el.removeAttribute('aria-hidden');
			try { el.inert = false; } catch (e) {}
		}
	}

	function tidy(){
		var ex = document.querySelector('.wc-block-components-express-payment');
		if (!ex) { return; }
		var rule = document.querySelector('.wc-block-components-express-payment-continue-rule');
		if (!rule) { var n = ex.nextElementSibling; if (n && /rule|divider|continue/i.test(n.className || '')) { rule = n; } }

		if (hasWalletButton(ex)) { everHadWallet = true; }

		// Once a real wallet has been seen, this page never collapses again.
		if (everHadWallet) {
			setCollapsed(ex, false); setCollapsed(rule, false);
			return;
		}
		// No wallet measured yet. Never collapse during the grace window: on a
		// wallet-capable device Stripe has simply not mounted yet, and a
		// premature collapse would be a visible flash for no reason.
		if (Date.now() - startedAt < GRACE_MS) {
			setCollapsed(ex, false); setCollapsed(rule, false);
			return;
		}
		setCollapsed(ex, true); setCollapsed(rule, true);
	}

	/*
	 * ⚠️ COALESCE. `tidy()` reads getBoundingClientRect(), which forces a
	 * synchronous layout. Watching attributes across the whole Blocks checkout
	 * subtree means the observer fires hundreds of times during React
	 * hydration, and calling tidy() directly from the callback made the
	 * checkout page unresponsive for minutes — observed, not theorised: the
	 * first QA run of this fix wedged a headless Chrome hard enough to time
	 * out the DevTools protocol twice.
	 *
	 * At most ONE measurement per animation frame, no matter how many
	 * mutations arrive. attributes:true is still exactly what we need — it is
	 * the only way to see Stripe size an existing iframe — it just must not be
	 * paid for once per mutation.
	 */
	var scheduled = false;
	function schedule(){
		if (scheduled) { return; }
		scheduled = true;
		var run = function(){ scheduled = false; tidy(); };
		if (typeof window.requestAnimationFrame === 'function') { window.requestAnimationFrame(run); }
		else { window.setTimeout(run, 16); }
	}

	function start(){
		tidy();
		var t = 0, iv = window.setInterval(function(){ tidy(); if (++t >= 40) { window.clearInterval(iv); } }, 400);
		var root = document.querySelector('.wp-block-woocommerce-checkout, .wc-block-checkout') || document.body;
		if (window.MutationObserver) {
			// attributes:true is the fix for the deaf observer. Stripe sizes an
			// EXISTING iframe by writing style/width/height — no childList
			// mutation is emitted for that.
			new MutationObserver(schedule).observe(root, {
				childList: true, subtree: true, attributes: true,
				attributeFilter: ['style', 'class', 'height', 'width', 'hidden']
			});
		}
		// A late layout settle (font swap, address panel expanding) changes
		// rects without touching the DOM at all.
		window.addEventListener('resize', schedule);
		window.addEventListener('load', schedule);
	}
	if (document.readyState !== 'loading') { start(); } else { document.addEventListener('DOMContentLoaded', start); }
})();
JS;
	wp_add_inline_script( 'bhp-express-tidy', $js );
}

/**
 * 2026-07-19 (Andrew, explicit): retire both lead-magnet popups sitewide.
 *
 * The quiz modal becomes the ONLY popup on the site. Suppressed via the
 * existing filters rather than by deleting the funnel code, so this is a
 * one-line reversal if the decision changes -- the templates, the shared
 * mariana-popup.js engine, the storage/event prefixes, the thank-you pages
 * and the Mailchimp tag mappings all remain intact and untouched.
 *
 * Funnel isolation is preserved by construction: neither popup renders, so
 * neither can affect the other's storage or analytics state.
 *
 * NOTE: this removes POPUP-driven email capture only. The inline signup
 * forms on the parent landing page, /teachers/, and the four audience
 * landing pages are unaffected and still feed their existing Mailchimp
 * funnels with their existing tags. No Mailchimp automation, tag, merge
 * field, or PDF was added or changed.
 */
add_filter( 'bhp_show_parent_popup', '__return_false' );
add_filter( 'bhp_show_teacher_popup', '__return_false' );

/* =====================================================================
 * CAPSTONE FIX WAVE — 2026-08-03
 * All three items below are Andrew rulings, relayed through the
 * Chief of Staff. All three are one-line reversals.
 * ===================================================================== */

/**
 * D1 — the quiz modal no longer auto-opens on /complete-collection/.
 *
 * Andrew's ruling: OFF on `/complete-collection/` only. It stays ON for `/`,
 * `/books/` and `/teachers/`, and cart / checkout / account / order-received
 * remain excluded upstream exactly as before.
 *
 * ⚠️ THIS RESOLVES A RECORDED CONTRADICTION AND DOES NOT PRETEND OTHERWISE.
 *    `bhp_should_autoopen_quiz()` in functions.php still carries Andrew's
 *    2026-07-19 statement that the quiz "must be eligible on every page a
 *    popup is allowed on at all". That comment is deliberately NOT edited:
 *    it was true when written and deleting it would hide that the policy
 *    changed. The 2026-08-02 ruling is later and narrower, and it is applied
 *    here rather than by rewriting the older record. Registered as
 *    CYCLE142-CX-004.
 *
 * WHY THE COLLECTION PAGE SPECIFICALLY. Measured, not asserted: the buy
 * button on that page sits at y=1824 on a 390x844 phone, so a customer
 * cannot physically reach it inside the modal's ~8,000ms timer. The modal is
 * full-screen, scroll-locking (`body{overflow:hidden}`) at z-index 10000, and
 * a tap at screen centre lands on `button.bhp-quiz__option`. It therefore
 * interrupts the format decision BY CONSTRUCTION, not by accident. That makes
 * this page a transaction surface, not a browsing one.
 *
 * The MANUAL launcher is untouched: `bhp_should_show_quiz_cta()` is not
 * filtered here, so a visitor who wants the quiz can still open it.
 */
add_filter( 'bhp_show_quiz_autoopen', 'bhp_capstone_quiz_autoopen_collection_off' );
function bhp_capstone_quiz_autoopen_collection_off( $enabled ) {
	if ( ! $enabled ) {
		return $enabled;
	}
	if ( is_page( 'complete-collection' ) || is_page_template( 'page-complete-collection.php' ) ) {
		return false;
	}

	/*
	 * ── B1 (2026-08-03) — HARDENING PASS, AND WHAT IT DOES AND DOES NOT FIX ──
	 *
	 * ⭐ FIRST, THE HONEST FINDING. Andrew reported "pop up quiz is not fixed
	 *    on staging". IT IS FIXED ON STAGING AND THE REPORT IS NOT
	 *    REPRODUCIBLE THERE. Measured with a real browser at 390x844,
	 *    `window.innerWidth` asserted, consent accepted, scrolled to 60% and
	 *    waited 13s on a FRESH session per page, against theme 1.19.150:
	 *
	 *      /complete-collection/   autoopen attr "false"  modal DID NOT open
	 *      /                       autoopen attr "true"   modal opened
	 *      /books/                 autoopen attr "true"   modal opened
	 *      /teachers/              autoopen attr "true"   modal opened
	 *
	 *    That is exactly the specified behaviour.
	 *
	 * ⛔ WHERE THE BEHAVIOUR HE DESCRIBES IS REAL: PRODUCTION. Read-only
	 *    check the same day, `braveheartspublishing.com/complete-collection/`
	 *    still renders `data-bhp-quiz-autoopen="true"` -- production is on
	 *    1.19.142 and has never received this filter. The defect is live
	 *    there and absent here, which is the single most likely explanation
	 *    for a report of "not fixed". IT CLOSES WHEN THE PRODUCTION PUSH
	 *    LANDS, and not before.
	 *
	 * ⭐ SECOND, A REAL GAP FOUND WHILE VERIFYING, AND FIXED HERE.
	 *    `/book-bundles/` (page 356) renders `[bhp_bundle_offers]` -- four
	 *    live `bhp_bundle_action` add-to-cart forms -- and the quiz DID
	 *    auto-open over it, measured on staging. It is a transaction surface
	 *    by exactly the reasoning that carved out `/complete-collection/`:
	 *    the modal is full-screen, scroll-locking and at z-index 10000, so it
	 *    interrupts a format decision by construction. `/shop-the-series/`
	 *    (page 359) 301s to `/complete-collection/` and was already covered.
	 *
	 *    The test below is BEHAVIOURAL, not a slug list: any page whose
	 *    content renders a bundle purchase form is excluded. A future bundle
	 *    page is therefore covered the day it is created, with no edit here.
	 *    A slug list would have to be remembered, and this one already was
	 *    not -- which is how `/book-bundles/` was missed the first time.
	 */
	if ( is_page() ) {
		$post = get_post();
		if ( $post instanceof WP_Post && has_shortcode( (string) $post->post_content, 'bhp_bundle_offers' ) ) {
			return false;
		}
		if ( $post instanceof WP_Post && has_shortcode( (string) $post->post_content, 'bhp_shop_the_series' ) ) {
			return false;
		}
	}

	return $enabled;
}

/**
 * D6 — ONE fulfilment sentence, sitewide.
 *
 * Andrew's ruling, verbatim target wording:
 *   "Printed and fulfilled by our publishing partner, Bookvault."
 * replacing "Printed and shipped by Bookvault" everywhere.
 *
 * ⛔ WHERE THE TWO SENTENCES ACTUALLY LIVE — searched, not assumed.
 *    Neither sentence exists anywhere in this theme or in the bundle plugin.
 *    Both live in the DATABASE, in product `post_content`:
 *      - product #333, "…The Mariana Trench (Paperback)"  -> "Printed and shipped by Bookvault"
 *      - product #12,  "…The Mariana Trench (Paperback)"  -> "Printed and shipped by"
 *      - product #15,  "…Mount Everest (Paperback)"       -> already correct
 *    (Four Privacy Policy REVISIONS also contain the old phrasing; the live
 *    Privacy Policy does not, so nothing customer-facing depends on them.)
 *
 * ⛔ PRODUCT RECORDS ARE AN ANDREW GATE AND WERE NOT EDITED. This filter
 *    normalises the sentence AT RENDER TIME so the customer-facing site is
 *    consistent tonight, while the underlying product content is left exactly
 *    as it is for Andrew to change. It rewrites nothing else: the match is an
 *    exact, anchored phrase, and any product that does not contain it is
 *    returned untouched, byte for byte.
 *
 * Remove this filter and the pages show the stored text again.
 */
add_filter( 'the_content', 'bhp_capstone_normalise_fulfilment_sentence', 20 );
add_filter( 'woocommerce_short_description', 'bhp_capstone_normalise_fulfilment_sentence', 20 );
function bhp_capstone_normalise_fulfilment_sentence( $content ) {
	if ( ! is_string( $content ) || '' === $content ) {
		return $content;
	}
	if ( false === stripos( $content, 'Bookvault' ) ) {
		return $content;
	}
	$canonical = 'Printed and fulfilled by our publishing partner, Bookvault.';
	$variants  = array(
		'Printed and shipped by Bookvault.' => $canonical,
		'Printed and shipped by Bookvault'  => rtrim( $canonical, '.' ),
	);
	return strtr( $content, $variants );
}

/**
 * F13 / CYCLE142-CX-014 — the hardcover PDP stops cross-selling the PAPERBACK
 * collection.
 *
 * Measured before: a hardcover product page carried the correct
 * "BEST VALUE COMPLETE COLLECTION $48.99" panel at y=825, and then, at
 * y=6057, a link reading "See the Complete Paperback Collection" — sending a
 * customer who has chosen the premium binding to the cheaper set.
 *
 * The destination URL is the same page either way (/complete-collection/
 * carries both formats and defaults to hardcover), so this is purely the
 * LABEL naming the wrong format. It is made format-aware from the product
 * being viewed rather than hardcoded, so it cannot drift again.
 */
add_filter( 'gettext', 'bhp_capstone_format_aware_collection_link', 20, 3 );
function bhp_capstone_format_aware_collection_link( $translated, $original, $domain ) {
	if ( 'brave-hearts' !== $domain || is_admin() ) {
		return $translated;
	}
	if ( 'See the Complete Paperback Collection' !== $original ) {
		return $translated;
	}
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return $translated;
	}
	$product = function_exists( 'wc_get_product' ) ? wc_get_product( get_queried_object_id() ) : null;
	if ( ! $product ) {
		return $translated;
	}
	if ( false !== stripos( (string) $product->get_name(), 'hardcover' ) ) {
		return __( 'See the Complete Hardcover Collection', 'brave-hearts' );
	}
	return $translated;
}
