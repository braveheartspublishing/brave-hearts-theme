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
	} elseif ( 0 === strpos( $action, 'offer_' ) && function_exists( 'bhp_offer_add_to_cart' ) ) {
		/*
		 * ⭐ 1.8.62 — THE OFFER ENGINE'S ADD PATH (`FD-579`).
		 *
		 * ⛔ SAME CONTRACT, NOT A NEW ONE. It arrives on the same
		 *    `bhp_bundle_action` field, behind the same `bhp_bundle_add` nonce
		 *    verified at the top of this function, and it leaves through the
		 *    same allowlisted redirect below. Nothing about the security or
		 *    the redirect model changes.
		 *
		 * ⛔ THE KEY IS VALIDATED AGAINST THE CATALOGUE, NOT TRUSTED. An
		 *    unknown key falls through to the `return` and adds nothing —
		 *    the posted string never reaches a product lookup, a price or a
		 *    redirect destination.
		 */
		$offer_key = substr( $action, strlen( 'offer_' ) );
		if ( ! array_key_exists( $offer_key, bhp_offer_catalog() ) ) {
			return;
		}
		/*
		 * ⛔⛔ THE PURCHASABILITY GATE, RE-ASSERTED AT THE CART DOOR. A surface
		 *    should never render a control for an unpurchasable offer — but a
		 *    POST is not a render, and a form can be replayed after a product
		 *    goes out of stock. Checking here means the gate cannot be walked
		 *    around by a stale page.
		 */
		if ( ! bhp_offer_is_purchasable( $offer_key ) ) {
			wc_add_notice( 'That offer is not available right now.', 'error' );
			return;
		}
		bhp_offer_add_to_cart( $offer_key );
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
	/*
	 * ⭐ 1.8.47 (2026-08-17, `CYCLE162-LD-TYP-V2`) — THE AUTO-APPLIED WELCOME
	 *    DISCOUNT, PRIMARY PATH. The books are in the cart and the redirect has
	 *    not happened yet, so this is the one deterministic, fully server-side
	 *    moment on the founder's 2-click route: thank-you CTA -> this form ->
	 *    checkout, with the discount already on it and no code typed.
	 *
	 * ⛔ IT IS A NO-OP WITHOUT A SESSION INTENT set by an earlier visit
	 *    carrying the thank-you CTA's param, and a no-op on every environment
	 *    where the coupon option is unset. An ordinary Collection purchase is
	 *    byte-identical to 1.8.46 — `tests/test-typ-auto-coupon.php` asserts
	 *    exactly that as a regression.
	 *
	 * ⛔ IT NEVER BLOCKS THE PURCHASE. Every failure route inside is silent and
	 *    returns false; the customer still lands where they were going.
	 */
	if ( function_exists( 'bhp_typ_maybe_apply_auto_coupon' ) ) {
		bhp_typ_maybe_apply_auto_coupon();
	}

	$destination = wc_get_cart_url();
	$requested   = isset( $_POST['bhp_bundle_redirect'] ) ? sanitize_key( wp_unslash( $_POST['bhp_bundle_redirect'] ) ) : '';
	if ( 'checkout' === $requested && ! wc_notice_count( 'error' ) && WC()->cart && ! WC()->cart->is_empty() ) {
		$destination = wc_get_checkout_url();
	}

	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐⭐ 1.8.67 — THE NO-JAVASCRIPT FLOOR UNDER THE SHOP CARD'S OFFER BUTTON.
	 *     CARRIER ITEM 210.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⭐ WHAT ACTUALLY HAPPENS ON A NORMAL VISIT, so this is read in
	 *    proportion: `interceptOfferForms()` in `bundle-drawer.js` claims this
	 *    submit, adds over the Store API and opens the side panel. No page
	 *    load, no redirect, nothing for this branch to do. THAT is the flow
	 *    item 210 describes and the flow QA walks.
	 *
	 * ⛔ THIS IS THE FLOOR UNDER IT: JavaScript off, or the Store API refusing.
	 *    Without this branch that shopper lands on the CART PAGE — the exact
	 *    surface carrier item 186 objected to ("I honestly dont like having
	 *    this cart page in the middle of a purchase"). ⭐ So the floor is the
	 *    offer's own product page, with the items really in the cart and
	 *    WooCommerce's own "added to cart" notice on it.
	 *
	 * ⛔ NOT A URL AND NOT CUSTOMER-CONTROLLED — the same discipline as the
	 *    `checkout` flag one paragraph up, and for the same reason. The posted
	 *    value is compared against ONE literal (`'1'`); the destination is
	 *    built from `get_permalink()` of a product this plugin resolved itself
	 *    out of `bhp_offer_components()`. ⛔ NO BYTE OF THE REQUEST IS EVER
	 *    CONCATENATED INTO IT. Do not "generalise" this to accept a URL.
	 *
	 * ⛔ AND IT NEVER OPENS THE PANEL. It sends no flag onward, because a URL
	 *    parameter that opens the panel is one that can be bookmarked, shared,
	 *    linked and crawled — Boromir's second condition, recorded in the
	 *    theme's `inc/purchase-flow.php`. A JavaScript-less shopper getting a
	 *    correct product page instead of a panel is a degradation; a panel any
	 *    link can open is a defect.
	 *
	 * ⛔ IT FAILS TO TODAY'S BEHAVIOUR: no flag, an error notice, an empty
	 *    cart, or an offer whose chapter component will not resolve → the cart
	 *    URL above stands, byte-identical to 1.8.66.
	 */
	if (
		isset( $_POST['bhp_offer_panel'] )
		&& '1' === sanitize_key( wp_unslash( $_POST['bhp_offer_panel'] ) )
		&& 0 === strpos( $action, 'offer_' )
		&& ! wc_notice_count( 'error' )
		&& WC()->cart && ! WC()->cart->is_empty()
		&& function_exists( 'bhp_offer_components' )
	) {
		$panel_components = bhp_offer_components( substr( $action, strlen( 'offer_' ) ) );
		foreach ( (array) $panel_components as $panel_component ) {
			if ( 'chapter' !== $panel_component['line'] ) {
				continue;
			}
			$panel_permalink = get_permalink( (int) $panel_component['product_id'] );
			if ( $panel_permalink ) {
				$destination = $panel_permalink;
			}
			break;
		}
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
	/*
	 * ⭐ 1.8.57 (2026-08-18, CYCLE164-LD-PAPERBACK-DEFAULT) — THE EXPLICIT
	 *    GUARD ON THIS FUNCTION'S OWN CONTRACT.
	 *
	 * ⛔ THIS IS BELT AND BRACES ON TOP OF SEAM 5, NOT INSTEAD OF IT.
	 *    `school-visit-paperback-only.php` seam 5 catches every caller of
	 *    `WC_Cart::add_to_cart()`; this one catches THIS caller by name and
	 *    gives the parent ONE clear sentence instead of three identical
	 *    per-title errors and a misleading "That bundle could not be added ...
	 *    the titles may be temporarily unavailable."
	 *
	 * ⛔ WHY BOTH: the reason seam 5 had to be written at all is that
	 *    `WC_Cart::add_to_cart()` does NOT apply
	 *    `woocommerce_add_to_cart_validation`, which is exactly the kind of
	 *    core assumption that is invisible until something is measured. A
	 *    format-level refusal in the function that takes a `$format` argument
	 *    does not depend on any core hook behaviour at all.
	 *
	 * ⛔ CONTROL PATH: false for every ordinary shopper, and for every
	 *    paperback add on any session.
	 */
	if ( 'hardcover' === $format
		&& function_exists( 'bhp_school_visit_paperback_only' )
		&& bhp_school_visit_paperback_only() ) {
		wc_add_notice( bhp_school_visit_paperback_only_message(), 'error' );
		return;
	}

	/*
	 * ⭐⭐ 1.8.71 (2026-08-24, CYCLE166-LD-VISIT-STOCK-GATE) — THE BOX CLOSES
	 *     WHEN **ANY ONE** OF ITS TITLES IS SOLD OUT FOR THE VISIT.
	 *
	 * ⛔ ALL-OR-NOTHING, AND THAT IS THE FOUNDER'S RULE AS CARRIED: "the
	 *    3-book box must close when ANY of its three titles is closed."
	 *    Partially filling a set is the failure this refuses. Seam 5 in
	 *    `school-visit-paperback-only.php` would otherwise refuse only the
	 *    CLOSED component from inside `WC_Cart::add_to_cart()`, leaving a
	 *    parent who paid for a three-book set holding two books and a
	 *    per-title error, having been charged the set discount for a set
	 *    that does not exist.
	 *
	 * ⛔ IT REFUSES BEFORE THE FIRST `add_to_cart()` CALL, not between them,
	 *    so no half-built cart ever exists to be cleaned up.
	 *
	 * ⛔ CONTROL PATH: `bhp_visit_shelf_title_is_closed_for_request()` is
	 *    false for every ordinary shopper on every environment, and false for
	 *    everyone on any environment where the shelf baseline is unset. This
	 *    whole block is inert until Andrew seeds `bhp_visit_shelf_stock`.
	 */
	if ( function_exists( 'bhp_visit_shelf_title_is_closed_for_request' ) ) {
		foreach ( $title_keys as $title_key ) {
			if ( bhp_visit_shelf_title_is_closed_for_request( $title_key ) ) {
				wc_add_notice(
					function_exists( 'bhp_visit_shelf_sold_out_message' )
						? bhp_visit_shelf_sold_out_message()
						: bhp_school_visit_paperback_only_message(),
					'error'
				);
				return;
			}
		}
	}

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

		/*
		 * ⭐ 1.8.57 (2026-08-18, CYCLE164-LD-PAPERBACK-DEFAULT): /book-bundles/
		 *    renders all four offers at once with no toggle, so "paperback only"
		 *    here means the two HARDCOVER sections are not rendered at all on a
		 *    school-visit session. Two purchase forms that the server would
		 *    refuse are worse than none.
		 *
		 * ⛔ CONTROL PATH: `bhp_school_visit_paperback_only()` is false for every
		 *    ordinary shopper and all four sections render exactly as in 1.8.56.
		 */
		if ( function_exists( 'bhp_school_visit_paperback_only' ) && bhp_school_visit_paperback_only() ) {
			$bhp_restricted_order = array_values( array_intersect( $bhp_offer_order, array( 'paperback' ) ) );
			if ( ! empty( $bhp_restricted_order ) ) {
				$bhp_offer_order = $bhp_restricted_order;
			}
		}

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

/*
 * ⭐ 1.8.71 (2026-08-24, CYCLE166-LD-VISIT-STOCK-GATE) — THE THREE RENDERERS
 *    BELOW NOW ASK THE SHELF BEFORE THEY OFFER A TITLE.
 *
 * ⛔ THEY DO NOT DECIDE ANYTHING. Each one reads
 *    `bhp_visit_shelf_closed_map_for_request()`, which is EMPTY for every
 *    ordinary shopper and empty on any environment with no shelf baseline
 *    set. The markup below is therefore byte-identical to 1.8.70 for
 *    everybody except a visit-flagged parent looking at a title Andrew has
 *    run out of.
 *
 * ⛔ A CLOSED TITLE IS SHOWN AND DISABLED, NOT HIDDEN. A parent who came for
 *    Everest needs to see that Everest was the thing that sold out; silently
 *    dropping the row reads as a bug and sends them looking for it.
 *
 * ⛔ A BOX CLOSES ENTIRELY. "Any two" needs two open titles; the complete
 *    collection needs all three. Offering a set the server refuses is the
 *    incoherence the colouring gate already established as the defect class.
 */

/**
 * Print the sold-out card that replaces a box whose titles are not all open.
 *
 * @param string $heading Card heading to keep the layout intact.
 * @return void
 */
function bhp_bundle_render_visit_sold_out_card( $heading ) {
	?>
	<div class="bhp-bundle-card bhp-bundle-card--sold-out">
		<h3><?php echo esc_html( $heading ); ?></h3>
		<p class="bhp-bundle-sold-out-label">
			<?php
			echo esc_html(
				function_exists( 'bhp_visit_shelf_sold_out_label' )
					? bhp_visit_shelf_sold_out_label()
					: 'Sold out for the school visit'
			);
			?>
		</p>
		<p class="bhp-bundle-sold-out-note">
			<?php
			echo esc_html(
				function_exists( 'bhp_visit_shelf_sold_out_message' )
					? bhp_visit_shelf_sold_out_message()
					: ''
			);
			?>
		</p>
	</div>
	<?php
}

function bhp_bundle_render_any2_section( $format, $action, $button_label ) {
	$catalog = bhp_bundle_catalog();
	$rules   = bhp_bundle_rules( $format );
	$rule    = $rules[2];

	$closed = function_exists( 'bhp_visit_shelf_closed_map_for_request' )
		? bhp_visit_shelf_closed_map_for_request()
		: array();

	// Fewer than two open titles: there is no "any two" left to offer.
	if ( ! empty( $closed )
		&& function_exists( 'bhp_visit_shelf_open_title_count' )
		&& bhp_visit_shelf_open_title_count( $format ) < 2 ) {
		bhp_bundle_render_visit_sold_out_card( $rule['heading'] );
		return;
	}
	?>
	<div class="bhp-bundle-card bhp-bundle-any2">
		<h3><?php echo esc_html( $rule['heading'] ); ?> - <?php echo esc_html( $rule['save'] ); ?></h3>
		<form method="post" class="bhp-bundle-form">
			<?php bhp_bundle_nonce_input(); /* F14: id-less nonce -- see bundle-landing-page.php */ ?>
			<input type="hidden" name="bhp_bundle_action" value="<?php echo esc_attr( $action ); ?>" />
			<p class="bhp-bundle-instructions">Choose exactly two different titles:</p>
			<ul class="bhp-bundle-title-list">
				<?php foreach ( $catalog[ $format ] as $title_key => $info ) : ?>
					<?php $is_closed = isset( $closed[ $title_key ] ); ?>
					<li<?php echo $is_closed ? ' class="bhp-bundle-title--sold-out"' : ''; ?>>
						<label>
							<input type="checkbox" name="bhp_titles[]" value="<?php echo esc_attr( $title_key ); ?>"<?php echo $is_closed ? ' disabled="disabled"' : ''; ?> />
							<?php echo esc_html( $info['label'] ); ?>
							<?php if ( $is_closed ) : ?>
								<span class="bhp-bundle-sold-out-label"><?php echo esc_html( bhp_visit_shelf_sold_out_label() ); ?></span>
							<?php elseif ( function_exists( 'bhp_visit_shelf_render_counter' ) ) : ?>
								<?php
								/*
								 * ⭐ 1.8.72 — the live count. See the note on the same
								 *    branch in `bundle-shop-series.php`. Nothing is
								 *    emitted outside the 2..10 window, and nothing at all
								 *    for an unflagged session.
								 */
								bhp_visit_shelf_render_counter( $title_key );
								?>
							<?php endif; ?>
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

	$closed = function_exists( 'bhp_visit_shelf_closed_map_for_request' )
		? bhp_visit_shelf_closed_map_for_request()
		: array();

	/*
	 * ⭐ THE FOUNDER'S RULE, LITERALLY: the three-book box closes when ANY of
	 *    its three titles is closed. `array_intersect_key` against the
	 *    format's own catalogue keys means a closed HARDCOVER title (there is
	 *    no such thing today) could never close the paperback box by accident.
	 */
	if ( ! empty( $closed ) && ! empty( array_intersect_key( $closed, $catalog[ $format ] ) ) ) {
		bhp_bundle_render_visit_sold_out_card( $rule['heading'] );
		return;
	}

	/*
	 * ⭐⭐ 1.8.72 (CYCLE166-LD-VISIT-STOCK-COUNTER) — THE THREE-BOOK BOX'S
	 *     COUNTER IS OFF BY DEFAULT. THIS IS THE JUDGMENT CALL, MADE.
	 *
	 * ⛔ RECOMMENDATION: show NOTHING on this card. The full reasoning lives on
	 *    `bhp_visit_shelf_counter_on_complete_box()` in
	 *    `school-visit-shelf-stock.php` so it is beside the switch and not
	 *    buried in a template. In one line: a single number on a three-title
	 *    card reads as "three SETS left" when it means "three copies of ONE of
	 *    these books", and the per-title list that says it unambiguously is
	 *    already on the same page, directly above this card, on BOTH surfaces
	 *    that render it.
	 *
	 * ⭐ THE FLIP IS ONE LINE AND NEEDS NO DEPLOY:
	 *      add_filter( 'bhp_visit_shelf_counter_on_complete_box', '__return_true' );
	 *    The branch below is fully implemented and covered by the suite, so
	 *    turning it on is a switch rather than a build.
	 *
	 * ⛔ WHEN ON, IT NAMES THE TITLE. An unnamed number here is the ambiguity
	 *    above; naming the constraining title removes it.
	 */
	$bhp_box_counter = '';
	if ( function_exists( 'bhp_visit_shelf_counter_on_complete_box' )
		&& bhp_visit_shelf_counter_on_complete_box()
		&& function_exists( 'bhp_visit_shelf_constraining_title_for_request' ) ) {
		$bhp_box_slug = bhp_visit_shelf_constraining_title_for_request( $format );
		if ( null !== $bhp_box_slug ) {
			$bhp_box_n = bhp_visit_shelf_counter_for_request( $bhp_box_slug );
			if ( null !== $bhp_box_n ) {
				$bhp_box_counter = bhp_visit_shelf_counter_label_named( $bhp_box_slug, (int) $bhp_box_n );
			}
		}
	}
	?>
	<div class="bhp-bundle-card bhp-bundle-complete">
		<span class="bhp-bundle-badge">Best Value - Get the Complete Collection</span>
		<h3><?php echo esc_html( $rule['heading'] ); ?> - <?php echo esc_html( $rule['save'] ); ?></h3>
		<ul class="bhp-bundle-title-list">
			<?php foreach ( $catalog[ $format ] as $info ) : ?>
				<li><?php echo esc_html( $info['label'] ); ?></li>
			<?php endforeach; ?>
		</ul>
		<?php if ( '' !== $bhp_box_counter ) : ?>
			<p class="bhp-bundle-stock-counter bhp-bundle-stock-counter--box"><?php echo esc_html( $bhp_box_counter ); ?></p>
		<?php endif; ?>
		<form method="post" class="bhp-bundle-form">
			<?php bhp_bundle_nonce_input(); /* F14: id-less nonce -- see bundle-landing-page.php */ ?>
			<input type="hidden" name="bhp_bundle_action" value="<?php echo esc_attr( $action ); ?>" />
			<button type="submit" class="button bhp-bundle-submit"><?php echo esc_html( $button_label ); ?></button>
		</form>
	</div>
	<?php
}
