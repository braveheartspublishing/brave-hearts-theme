<?php
/**
 * Brave Hearts Bundle Pricing — storefront selector UI.
 *
 * Renders the four bundle offers via the [bhp_bundle_offers] shortcode and
 * handles the "add bundle to cart" form submissions. Every button here adds
 * the real, individually-mapped WooCommerce products as separate cart line
 * items — this file never creates or adds a standalone "bundle" product.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'bhp_bundle_offers', 'bhp_bundle_render_offers' );

/**
 * Handle bundle "add to cart" form posts before any output is sent, so a
 * redirect to the cart can happen cleanly on success.
 */
add_action( 'template_redirect', 'bhp_bundle_handle_add_to_cart' );
function bhp_bundle_handle_add_to_cart() {
	if ( empty( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST['bhp_bundle_action'] ) ) {
		return;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	if ( ! isset( $_POST['bhp_bundle_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bhp_bundle_nonce'] ) ), 'bhp_bundle_add' ) ) {
		wc_add_notice( 'Your bundle selection could not be verified. Please try again.', 'error' );
		return;
	}

	$action  = sanitize_text_field( wp_unslash( $_POST['bhp_bundle_action'] ) );
	$catalog = bhp_bundle_catalog();

	if ( 'complete_paperback' === $action ) {
		bhp_bundle_add_titles_to_cart( 'paperback', array_keys( $catalog['paperback'] ) );
	} elseif ( 'complete_hardcover' === $action ) {
		bhp_bundle_add_titles_to_cart( 'hardcover', array_keys( $catalog['hardcover'] ) );
	} elseif ( 'complete_paperback_smart' === $action ) {
		bhp_bundle_add_missing_titles_to_cart( 'paperback', array_keys( $catalog['paperback'] ) );
	} elseif ( 'complete_hardcover_smart' === $action ) {
		bhp_bundle_add_missing_titles_to_cart( 'hardcover', array_keys( $catalog['hardcover'] ) );
	} elseif ( in_array( $action, array( 'any2_paperback', 'any2_hardcover' ), true ) ) {
		$format   = ( 'any2_paperback' === $action ) ? 'paperback' : 'hardcover';
		$posted   = isset( $_POST['bhp_titles'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['bhp_titles'] ) ) : array();
		$selected = array_values( array_unique( array_intersect( $posted, array_keys( $catalog[ $format ] ) ) ) );

		// Eligibility rule (Phase 4): exactly two different titles.
		if ( 2 !== count( $selected ) ) {
			wc_add_notice( sprintf( 'Please choose exactly two different %s titles for this bundle.', $format ), 'error' );
			return;
		}
		bhp_bundle_add_titles_to_cart( $format, $selected );
	} elseif ( in_array( $action, array( 'single_paperback', 'single_hardcover' ), true ) ) {
		$format = ( 'single_paperback' === $action ) ? 'paperback' : 'hardcover';
		$title  = isset( $_POST['bhp_single_title'] ) ? sanitize_text_field( wp_unslash( $_POST['bhp_single_title'] ) ) : '';

		if ( ! isset( $catalog[ $format ][ $title ] ) ) {
			wc_add_notice( sprintf( 'Please choose one %s title.', $format ), 'error' );
			return;
		}
		bhp_bundle_add_titles_to_cart( $format, array( $title ) );
	} else {
		return;
	}

	/*
	 * B7 (2026-08-03). Andrew, walk-3, verbatim: "I want less steps to
	 * purchase." A form may ask to land on CHECKOUT instead of the cart by
	 * posting bhp_bundle_redirect=checkout.
	 *
	 * ⛔ ALLOWLISTED, NEVER AN ARBITRARY URL. The posted value is compared
	 *    against one literal and the destination is built by WooCommerce's own
	 *    wc_get_checkout_url(); nothing from the request ever reaches
	 *    wp_safe_redirect() as a URL. An open redirect here would be a real
	 *    vulnerability on a page that takes payment.
	 *
	 * Default is UNCHANGED: every existing form omits the field and still
	 * lands on the cart, so no current path is altered by this addition.
	 *
	 * If anything failed to add, wc_add_notice() has already recorded an
	 * error and the cart is the honest destination -- a customer must not be
	 * dropped onto checkout with a cart that is not what they asked for.
	 */
	$destination = wc_get_cart_url();
	$requested   = isset( $_POST['bhp_bundle_redirect'] ) ? sanitize_key( wp_unslash( $_POST['bhp_bundle_redirect'] ) ) : '';
	if ( 'checkout' === $requested && ! wc_notice_count( 'error' ) && WC()->cart && ! WC()->cart->is_empty() ) {
		$destination = wc_get_checkout_url();
	}

	wp_safe_redirect( $destination );
	exit;
}

/**
 * P2-5 (2026-08-03) — the Collection page's buy CTAs go straight to checkout.
 *
 * Andrew, verbatim, relayed through the Chief of Staff and not witnessed by
 * this agent: *"on the collection page- it says get the collection - it should
 * go straight to check out or cart and auto check out with one click"*.
 *
 * This emits the one hidden field that turns any `bhp-bundle-form` into a
 * one-tap add-and-checkout. It exists as a function rather than as three
 * copies of the same `<input>` so that a future change to the mechanism
 * cannot land on two of the three Collection CTAs and be missed on the third —
 * which is exactly the class of drift that produced the sticky bar and the
 * panel disagreeing about format in the first place.
 *
 * ⛔ WHAT THIS IS NOT. It is not a URL, not a parameter, and not
 *    customer-controlled. `bhp_bundle_handle_add()` compares the posted value
 *    against ONE literal and then builds the destination from WooCommerce's own
 *    `wc_get_checkout_url()`. Nothing from the request ever reaches
 *    `wp_safe_redirect()`. On a page that takes payment, an open redirect would
 *    be a real vulnerability, so the allowlist is the design, not a nicety.
 *
 * The bundle mechanics are untouched: the same `complete_{format}_smart`
 * action, the same three real product line items, the same discount as a
 * WooCommerce fee, the same "already in the cart" de-duplication. The only
 * thing that changes is where the customer lands afterwards.
 *
 * The FORMAT PICKER still governs. Each CTA posts the action that
 * `bundle-landing.js` keeps in step with the selected format, so the format the
 * customer chose is the format that reaches the cart.
 */
function bhp_bundle_checkout_redirect_input() {
	echo '<input type="hidden" name="bhp_bundle_redirect" value="checkout" />';
}

/**
 * Add each selected title to the cart as its own real, mapped product line
 * item (never a substitute bundle product).
 */
function bhp_bundle_add_titles_to_cart( $format, array $title_keys ) {
	$catalog = bhp_bundle_catalog();
	$added   = 0;

	foreach ( $title_keys as $title_key ) {
		if ( ! isset( $catalog[ $format ][ $title_key ] ) ) {
			continue;
		}
		$info = $catalog[ $format ][ $title_key ];
		$ok   = $info['variation_id']
			? WC()->cart->add_to_cart( $info['product_id'], 1, $info['variation_id'] )
			: WC()->cart->add_to_cart( $info['product_id'], 1 );
		if ( $ok ) {
			++$added;
		}
	}

	if ( $added > 0 ) {
		wc_add_notice( 'Bundle added to your cart.', 'success' );
	} else {
		wc_add_notice( 'That bundle could not be added to your cart - the titles may be temporarily unavailable.', 'error' );
	}
}

/**
 * "Smart" complete-set add, used by the premium complete-series landing page
 * (Staging Refinement Phase 2, section 7): adds only the titles NOT already
 * in the cart for this format, so a customer who already has one or two
 * matching-format titles never gets a duplicate line item or an
 * unintentional quantity bump on a title they already chose. Mirrors the
 * client-side filtering in bundle-drawer.js's interceptBundleForms() — this
 * is the no-JS/server-side fallback path for the same behavior.
 */
function bhp_bundle_add_missing_titles_to_cart( $format, array $all_title_keys ) {
	$distinct = bhp_bundle_distinct_titles_in_cart( WC()->cart );
	$missing  = array_values( array_diff( $all_title_keys, $distinct[ $format ] ) );

	if ( empty( $missing ) ) {
		wc_add_notice( 'You already have the complete set in your cart.', 'success' );
		return;
	}

	bhp_bundle_add_titles_to_cart( $format, $missing );
}

function bhp_bundle_render_offers() {
	ob_start();

	// Print any notices (e.g. the "choose exactly two" validation error)
	// directly here so they show up regardless of which page/template the
	// shortcode is placed on, not only on native WooCommerce templates.
	wc_print_notices();
	?>
	<div class="bhp-bundle-offers">
		<?php
		/*
		 * 2D (2026-08-03) -- HARDCOVER-FIRST ON /book-bundles/.
		 *
		 * Andrew, walk-4, verbatim (RELAYED through the Chief of Staff, NOT
		 * witnessed by this agent): "all the funnel pages and collection pages
		 * should default to the hardcovers not paperback".
		 *
		 * This page has no toggle and no default to set -- all four offers are
		 * rendered at once -- so "default to hardcover" can only mean ORDER
		 * here. The two hardcover cards move above the two paperback cards, and
		 * the order is READ from bhp_bundle_default_format() rather than being
		 * a second hardcoded copy of the same decision.
		 *
		 * ⛔ NOTHING ELSE CHANGES. Same four sections, same four
		 *    bhp_bundle_action values, same catalog, same bhp_bundle_rules()
		 *    figures, same nonce, same handler. No price, discount, shipping
		 *    rule or product mapping is touched by a reordering.
		 */
		$bhp_offer_default = function_exists( 'bhp_bundle_default_format' ) ? bhp_bundle_default_format() : 'hardcover';
		$bhp_offer_order   = ( 'paperback' === $bhp_offer_default )
			? array( 'paperback', 'hardcover' )
			: array( 'hardcover', 'paperback' );

		$bhp_offer_labels = array(
			'paperback' => array(
				'any2'     => array( 'any2_paperback', 'Add My 2-Book Paperback Set' ),
				'complete' => array( 'complete_paperback', 'Add the Complete Paperback Set' ),
			),
			'hardcover' => array(
				'any2'     => array( 'any2_hardcover', 'Add My 2-Book Hardcover Set' ),
				'complete' => array( 'complete_hardcover', 'Add the Complete Hardcover Collection' ),
			),
		);

		foreach ( $bhp_offer_order as $bhp_offer_format ) {
			$bhp_offer = $bhp_offer_labels[ $bhp_offer_format ];
			bhp_bundle_render_any2_section( $bhp_offer_format, $bhp_offer['any2'][0], $bhp_offer['any2'][1] );
			bhp_bundle_render_complete_section( $bhp_offer_format, $bhp_offer['complete'][0], $bhp_offer['complete'][1] );
		}
		?>
	</div>
	<?php
	return ob_get_clean();
}

function bhp_bundle_render_any2_section( $format, $action, $button_label ) {
	$catalog = bhp_bundle_catalog();
	$rules   = bhp_bundle_rules( $format );
	$rule    = $rules[2];
	?>
	<div class="bhp-bundle-card bhp-bundle-any2">
		<h3><?php echo esc_html( $rule['heading'] ); ?> - <?php echo esc_html( $rule['save'] ); ?></h3>
		<form method="post" class="bhp-bundle-form">
			<?php bhp_bundle_nonce_input(); /* F14: id-less nonce -- see bundle-landing-page.php */ ?>
			<input type="hidden" name="bhp_bundle_action" value="<?php echo esc_attr( $action ); ?>" />
			<p class="bhp-bundle-instructions">Choose exactly two different titles:</p>
			<ul class="bhp-bundle-title-list">
				<?php foreach ( $catalog[ $format ] as $title_key => $info ) : ?>
					<li>
						<label>
							<input type="checkbox" name="bhp_titles[]" value="<?php echo esc_attr( $title_key ); ?>" />
							<?php echo esc_html( $info['label'] ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
			<button type="submit" class="button bhp-bundle-submit"><?php echo esc_html( $button_label ); ?></button>
		</form>
	</div>
	<?php
}

function bhp_bundle_render_complete_section( $format, $action, $button_label ) {
	$catalog = bhp_bundle_catalog();
	$rules   = bhp_bundle_rules( $format );
	$rule    = $rules[3];
	?>
	<div class="bhp-bundle-card bhp-bundle-complete">
		<span class="bhp-bundle-badge">Best Value - Get the Complete Collection</span>
		<h3><?php echo esc_html( $rule['heading'] ); ?> - <?php echo esc_html( $rule['save'] ); ?></h3>
		<ul class="bhp-bundle-title-list">
			<?php foreach ( $catalog[ $format ] as $info ) : ?>
				<li><?php echo esc_html( $info['label'] ); ?></li>
			<?php endforeach; ?>
		</ul>
		<form method="post" class="bhp-bundle-form">
			<?php bhp_bundle_nonce_input(); /* F14: id-less nonce -- see bundle-landing-page.php */ ?>
			<input type="hidden" name="bhp_bundle_action" value="<?php echo esc_attr( $action ); ?>" />
			<button type="submit" class="button bhp-bundle-submit"><?php echo esc_html( $button_label ); ?></button>
		</form>
	</div>
	<?php
}
