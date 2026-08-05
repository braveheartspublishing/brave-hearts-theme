<?php
/**
 * ONE-CLICK COMPLETE-COLLECTION CTA — the whole set into the cart, then
 * straight to /checkout/.
 * ============================================================================
 *
 * Andrew Signore, 2026-08-05, current-turn order (RELAYED through the Chief of
 * Staff in the brief that commissioned this file; NOT witnessed by this agent):
 *
 *   "when you finally hit 'Add hard cover collection' and the 'Collection' CTA
 *    in the footer bar pop up- it takes you directly to the collection page- it
 *    should automatically - add the books to your cart and take you to the
 *    checkout page for a 2 click journey to purchase- WE HAVE TO SIMPLIFY THE
 *    PURCHASE PATH FOR ALL CTA buttons"
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * ⛔ THIS FILE INVENTS NO COMMERCE MECHANISM. IT REUSES THE SHIPPED ONE.
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Everything below is a thin renderer over the bundle plugin's existing,
 * already-live `form.bhp-bundle-form` contract — the exact same contract the
 * Complete Collection page's three buy CTAs have used since P2-5 (2026-08-03):
 *
 *   - `bhp_bundle_nonce_input()`             — plugin-owned nonce (`bhp_bundle_add`)
 *   - `bhp_bundle_action=complete_{fmt}_smart` — plugin-owned "add only the
 *                                              titles this cart is missing"
 *   - `bhp_bundle_checkout_redirect_input()` — plugin-owned, ALLOWLISTED
 *                                              "finish on /checkout/" flag
 *
 * Both halves of that contract already exist and both are exercised on every
 * real visit:
 *
 *   - NO-JS / server path: `bhp_bundle_handle_add_to_cart()` on `template_redirect`
 *     in `plugins/brave-hearts-bundle-pricing/includes/bundle-shortcode.php`.
 *     It POSTs, adds, then `wp_safe_redirect()`s. **That redirect-after-POST is
 *     what makes a browser refresh safe** — the browser's final URL is a GET of
 *     /checkout/, so F5 re-requests the checkout page, never the add.
 *   - JS path: `interceptBundleForms()` + `finishBundleAdd()` in
 *     `plugins/brave-hearts-bundle-pricing/assets/bundle-drawer.js`. It
 *     `preventDefault()`s, adds over the Store API `/batch` endpoint, and — when
 *     `bhp_bundle_redirect=checkout` is present — `location.assign()`s to
 *     checkout instead of opening the side drawer. `initBundleFormFeedback()`
 *     disables the button on first submit, so a double-tap cannot double-add.
 *
 * ⛔ THE REDIRECT VALUE IS NOT A URL AND IS NOT CUSTOMER-CONTROLLED. The plugin
 *    compares the posted value against ONE literal and then builds the
 *    destination from WooCommerce's own `wc_get_checkout_url()`. Nothing from
 *    the request ever reaches `wp_safe_redirect()`. On a page that takes
 *    payment an open redirect would be a real vulnerability, so this is the
 *    design, not a nicety. Do not "generalise" it to accept a destination.
 *
 * ⛔ NOTHING HERE TOUCHES PRICE, DISCOUNT, SHIPPING, TAX, STOCK, A PRODUCT
 *    RECORD, A SKU OR A BOOKVAULT MAPPING. The three real books are added as
 *    three real, individually-mapped line items exactly as before; the bundle
 *    discount is still the plugin's own cart fee; collection shipping is still
 *    whatever `bhp_bundle_rules()` says (FREE since 1.8.23). This file only
 *    changes WHERE THE CUSTOMER LANDS.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * WHY A SHARED RENDERER RATHER THAN THE MARKUP PASTED INTO EACH TEMPLATE
 * ─────────────────────────────────────────────────────────────────────────────
 * Because the last time a purchase control was pasted into several templates,
 * the Collection page's sticky bar and its pricing panel drifted apart on
 * format and had to be re-unified. Five landing pages × up to three CTAs each
 * is fifteen chances to fix a bug in fourteen places. One function, one
 * contract, one place to change it.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * FAILS CLOSED, AND FAILS TO SOMETHING THAT STILL SELLS
 * ─────────────────────────────────────────────────────────────────────────────
 * If the bundle plugin is deactivated, every helper above is undefined. Rather
 * than emit a dead form, this renders the ORIGINAL `<a href="/complete-collection/">`
 * link with the same label, classes and analytics attributes. A customer on a
 * plugin-less site sees exactly the pre-2026-08-05 behaviour, not a broken button.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The single shared style token every Complete-Collection CTA carries.
 *
 * Declared as a constant rather than typed into six templates because the last
 * time a purchase control's classes were pasted around, the Collection page's
 * sticky bar and its pricing panel drifted apart and had to be re-unified. One
 * name, one CSS rule, one place to change it.
 *
 * @see bhp_collection_add_to_cart_cta() — appends it to every rendered control.
 * @see style.css § "THE COLLECTION-CTA NAV-MATCH TOKEN" — the only rule that reads it.
 */
const BHP_COLLECTION_CTA_CLASS = 'bhp-collection-cta__btn';

/**
 * Can we render a real one-click add-and-checkout control right now?
 *
 * All three are plugin-owned. `bhp_bundle_catalog()` is checked too because a
 * form whose action names a format the catalog cannot resolve would add
 * nothing and drop the customer back on the cart with an error notice.
 *
 * @return bool
 */
function bhp_collection_cta_available() {
	return function_exists( 'bhp_bundle_nonce_input' )
		&& function_exists( 'bhp_bundle_checkout_redirect_input' )
		&& function_exists( 'bhp_bundle_catalog' );
}

/**
 * The format a format-agnostic CTA (sticky bar, final CTA) should post.
 *
 * Delegates to the theme's single source of truth, which itself delegates to
 * the bundle plugin — so this can never disagree with the pre-selected control
 * on the price card above it.
 *
 * @return string 'paperback'|'hardcover'
 */
function bhp_collection_cta_default_format() {
	return function_exists( 'bhp_book_default_format' ) ? bhp_book_default_format() : 'hardcover';
}

/**
 * Render a "put the whole set in the cart and take me to checkout" control.
 *
 * @param array $args {
 *     @type string $format     'paperback'|'hardcover'. Required in practice;
 *                              falls back to the default format.
 *     @type string $label      Button text. Passed through esc_html().
 *     @type string $class      Button classes (e.g. 'btn btn-primary').
 *     @type string $form_class Extra classes on the <form>.
 *     @type bool   $sync       TRUE marks the hidden action field with
 *                              `data-bhp-collection-action` so the page's
 *                              format toggle keeps it in step. Use on controls
 *                              that live OUTSIDE a per-format panel (sticky
 *                              bar, final CTA). A control inside a per-format
 *                              panel already knows its format — leave FALSE.
 *     @type string $event      data-bhp-event value (nav.js reads this).
 *     @type string $source     data-bhp-source value.
 *     @type string $extra      Raw extra attributes, already escaped by caller.
 * }
 * @return string HTML.
 */
function bhp_collection_add_to_cart_cta( array $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'format'     => '',
			'label'      => __( 'Get the Complete Collection', 'brave-hearts' ),
			'class'      => 'btn btn-primary',
			'form_class' => '',
			'sync'       => false,
			'event'      => '',
			'source'     => '',
			'extra'      => '',
		)
	);

	$format = in_array( $args['format'], array( 'paperback', 'hardcover' ), true )
		? $args['format']
		: bhp_collection_cta_default_format();

	$data_attrs = '';
	if ( $args['event'] ) {
		$data_attrs .= ' data-bhp-event="' . esc_attr( $args['event'] ) . '"';
	}
	if ( $args['source'] ) {
		$data_attrs .= ' data-bhp-source="' . esc_attr( $args['source'] ) . '"';
	}
	if ( $args['extra'] ) {
		$data_attrs .= ' ' . $args['extra'];
	}

	/*
	 * ⭐ 1.19.197 (2026-08-05) — THE ONE STYLE TOKEN EVERY COLLECTION CTA CARRIES.
	 *
	 * Andrew Signore, 2026-08-05, current-turn order, verbatim (RELAYED through
	 * the Chief of Staff; NOT witnessed by this agent):
	 *
	 *   "All the CTA 'Get the Collections' need to match the nav bar button"
	 *
	 * `BHP_COLLECTION_CTA_CLASS` is appended to whatever class the caller asked
	 * for, on BOTH branches below, so a control's identity (`btn btn-primary`,
	 * `btn btn-outline-light`, `home-collection-feature__cta`, …) is untouched
	 * and only the shared treatment is added. The rule that consumes it lives
	 * ONCE, in style.css beside the sitewide primary-CTA palette, and it sets
	 * ONLY the properties that constitute the nav button's LOOK — colour,
	 * border, radius, typeface, weight, tracking, case, hover and focus.
	 *
	 * ⛔ IT DELIBERATELY SETS NO font-size, padding, min-height, display OR
	 *    width. Andrew's instruction is that they MATCH the nav button, not that
	 *    they BECOME it: a 340px homepage band CTA and a 13px sticky-bar chip
	 *    are correct at different sizes and wrong at the same one. Geometry
	 *    therefore stays exactly where each surface already had it, and this
	 *    token cannot start a size war with the F1 button-spec bundle.
	 *
	 * ⛔ NO BEHAVIOUR CHANGES HERE. Same form, same nonce, same action, same
	 *    redirect flag, same analytics attributes, same fail-closed anchor. The
	 *    only difference in the emitted HTML is one extra class token.
	 *
	 * ⛔ THE HEADER'S OWN TWO CONTROLS DO **NOT** CARRY THE TOKEN, AND THAT IS
	 *    CORRECT — STATED HERE BECAUSE IT LOOKS LIKE AN OVERSIGHT AND IS NOT.
	 *    1.19.196 reverted them to plain anchors, so they no longer route through
	 *    this function at all. They are the REFERENCE, not a follower: they are
	 *    styled by `.header-expedition-cta` / `.site-nav__cta` in the sitewide
	 *    primary-CTA palette, and the token rule reads the SAME custom properties
	 *    that palette reads (`--cta-primary-bg`, `--cta-primary-text`,
	 *    `--cta-primary-border`, `--btn-radius`, `--btn-font`, `--btn-tracking`).
	 *    Parity is therefore structural rather than copied — which is the failure
	 *    mode recorded at ~line 690 of style.css, where hardcoded colours produced
	 *    a gold/navy mobile CTA against a green/ivory desktop one on 2026-07-14.
	 *
	 * ⚠ ALSO STATED RATHER THAN DISCOVERED LATER: the header anchor's GEOMETRY
	 *   moved in 1.19.196 as a side effect of the revert — `button[type="submit"]`
	 *   (0,1,1) out-ranked the header's own single-class rules, so the 1.19.183
	 *   BUTTON rendered at min-height 48px / padding 14px 32px, while the restored
	 *   ANCHOR takes the intended 44px / 0.7em 1.15em. That is precisely why this
	 *   token sets no geometry: had it copied the reference's measured pixels, it
	 *   would have frozen a number that the concurrent revert was already changing.
	 */
	$control_class = trim( $args['class'] . ' ' . BHP_COLLECTION_CTA_CLASS );

	/*
	 * FAIL-CLOSED FALLBACK. Plugin off -> the page keeps the exact link it had
	 * before this feature existed. Same label, same classes, same analytics.
	 */
	if ( ! bhp_collection_cta_available() ) {
		return sprintf(
			'<a href="%s" class="%s"%s>%s</a>',
			esc_url( home_url( '/complete-collection/' ) ),
			esc_attr( $control_class ),
			$data_attrs,
			esc_html( $args['label'] )
		);
	}

	ob_start();
	?>
	<form method="post" class="bhp-bundle-form bhp-collection-cta <?php echo esc_attr( $args['form_class'] ); ?>">
		<?php bhp_bundle_nonce_input(); ?>
		<input type="hidden" name="bhp_bundle_action" value="<?php echo esc_attr( 'complete_' . $format . '_smart' ); ?>"<?php echo $args['sync'] ? ' data-bhp-collection-action' : ''; ?> />
		<?php bhp_bundle_checkout_redirect_input(); ?>
		<button type="submit" class="<?php echo esc_attr( $control_class ); ?>"<?php echo $data_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each component escaped above ?>><?php echo esc_html( $args['label'] ); ?></button>
	</form>
	<?php
	return trim( ob_get_clean() );
}

/*
 * ============================================================================
 * ⛔ SUPERSEDED — THE SITEWIDE HEADER CONTROL AS BUILT IN 1.19.183
 * ============================================================================
 *
 * ⛔⛔ EVERYTHING IN THIS BLOCK DESCRIBES BEHAVIOUR THAT NO LONGER SHIPS. It is
 *     preserved verbatim, and deliberately NOT corrected in place, because
 *     Andrew reversed it the same day after using it — and a future reader who
 *     finds only the outcome cannot tell a considered reversal from a
 *     regression. The controlling block is "THE REVERSAL", immediately below.
 *
 * Andrew Signore, 2026-08-05, current-turn ruling, item 8, verbatim:
 *
 *   "Convert to hardcover purchase"
 *
 * ⛔ RELAYED through the Chief of Staff in the brief that commissioned this
 *    code. THIS AGENT DID NOT WITNESS IT. It is recorded with its speaker and
 *    its channel rather than as an unattributed requirement.
 *
 * Scope: the header's "GET THE COMPLETE COLLECTION" button — the top-right
 * `.header-expedition-cta` on desktop/tablet and its mobile-dropdown
 * counterpart `.site-nav__cta` — stops navigating to `/complete-collection/`
 * and instead adds the HARDCOVER set and lands the customer on `/checkout/`.
 *
 * ⛔ NO NEW COMMERCE MECHANISM. This is the same `bhp_collection_add_to_cart_cta()`
 *    contract the four funnel pages, the homepage band and the /books/ banner
 *    already post through, which is why nothing here re-derives a nonce, an
 *    action name or a redirect flag.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * WHY 'hardcover' IS A LITERAL HERE AND NOT `bhp_collection_cta_default_format()`
 * ─────────────────────────────────────────────────────────────────────────────
 * Because Andrew's ruling names the format. The two currently agree —
 * `bhp_bundle_default_format()` returns 'hardcover' — so today the literal and
 * the helper produce identical markup. They are NOT the same requirement: if the
 * site default is ever changed for the Collection landing page, the header must
 * keep selling what Andrew specified until he says otherwise. A test asserts the
 * literal, and asserts the current agreement separately, so the day they diverge
 * is a decision rather than a silent behaviour change.
 *
 * `sync => false` for the same reason: there is no format toggle in the header,
 * so there is nothing for the page's toggle to keep this control in step with.
 * Marking it `sync => true` would let a paperback toggle on the page below
 * silently rewrite a header button Andrew specified as hardcover.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * ⛔ THE HEADER RENDERS ON /cart/ AND /checkout/ TOO — THAT IS DECIDED, NOT LEFT
 * ─────────────────────────────────────────────────────────────────────────────
 * `bhp_collection_cta_context_allows_add()` below suppresses the purchase form
 * on the cart and checkout pages and restores the plain link there. Reasoning,
 * recorded so it is not re-litigated:
 *
 *   - On /checkout/ a customer is mid-payment. A sitewide button that silently
 *     mutates the cart they are about to pay for is a real defect class, not a
 *     styling question — and it would fire on the very screen where the totals
 *     they just read would change underneath them.
 *   - `is_checkout()` is also true on the ORDER-RECEIVED endpoint. Adding three
 *     books to a cart on a thank-you page, one click after an order completed,
 *     is the worst instance of the same problem.
 *   - On /cart/ the customer is already inside the purchase path with the cart
 *     in front of them; the drawer and the cart's own controls cover adding.
 *
 * The suppression is a FALLBACK, not a removal: the button stays in the header
 * with the same label, classes and destination it had before 2026-08-05. A
 * customer on /cart/ who clicks it still reaches the Collection page.
 *
 * ⚠ ONE DELIBERATE COSMETIC LOSS, STATED RATHER THAN DISCOVERED LATER: the form
 *   variant drops `aria-current="page"`, and with it the gold ring
 *   `.header-expedition-cta[aria-current="page"]` draws on /complete-collection/.
 *   `aria-current="page"` asserts "this control points at the page you are on".
 *   Once the control is a purchase button that posts and lands on /checkout/,
 *   that assertion is false, and shipping a false one to assistive technology to
 *   preserve a box-shadow is the wrong trade. The link fallback still carries it.
 */

/*
 * ============================================================================
 * ⭐ THE REVERSAL — 2026-08-05, theme 1.19.192. THIS BLOCK CONTROLS.
 * ============================================================================
 *
 * Andrew Signore, 2026-08-05, current-turn ruling, verbatim, in full:
 *
 *   "So the 'Get Complete Collection' CTA in the nav bar isnt centered -center
 *    it- bring the box up. Also when you add to cart from that CTA button there
 *    is a shifting of the words on the nav bar goes back and forth. Also when
 *    you click the Get the Complete Collection - the UX - says adding to cart -
 *    and its quite slow - It should already be rendered and cached- I want to
 *    make a big change- the nav bar get the complete collection should go to the
 *    collection page not the checkout. Then we have the already rendered and
 *    cached pages on checkout for both the paperback and hardcovers"
 *
 * ⛔ RELAYED through the Chief of Staff, preserved verbatim on disk in the
 *    execution register. THIS AGENT DID NOT WITNESS IT.
 *
 * ⭐ THIS IS A KNOWING REVERSAL BY ANDREW OF HIS OWN SAME-DAY ITEM-8 RULING,
 *    MADE ON HIS OWN DIRECT EXPERIENCE OF THE SHIPPED FLOW — not a defect
 *    report, not a rediscovery, and not this agent's judgement. Recorded that
 *    way so nobody "restores" the form later thinking a build was lost.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * SCOPE — ONE SURFACE. THE PURCHASE PATH IS NOT BEING WALKED BACK.
 * ─────────────────────────────────────────────────────────────────────────────
 * ✅ REVERTED, both header controls, UNCONDITIONALLY: the top-right
 *    `.header-expedition-cta` and the mobile-dropdown `.site-nav__cta` are
 *    plain anchors to `/complete-collection/` again.
 * ⛔ UNCHANGED, and deliberately so: `bhp_collection_add_to_cart_cta()` above
 *    and every one of its callers — the Complete Collection page's own format
 *    toggle and buy CTAs, the four funnel pages, the homepage Best-Value band
 *    and the /books/ banner. Those ARE the two-click purchase path, and
 *    Andrew's own sentence says why the header no longer needs to be one:
 *    "we have the already rendered and cached pages on checkout for both the
 *    paperback and hardcovers."
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * WHY A LINK IS FASTER HERE, AND IT IS NOT A MATTER OF TASTE
 * ─────────────────────────────────────────────────────────────────────────────
 * The form could only ever be slow, on every click, for reasons no amount of
 * tuning removes:
 *
 *   - A POST is never served from the page cache. `/complete-collection/` is a
 *     cached GET; the add-and-redirect is an uncacheable POST followed by a
 *     second full navigation to /checkout/, which is itself uncacheable.
 *   - The JS path replaces that with a Store API `/batch` round-trip plus a
 *     `location.assign()` — still two sequential network waits before anything
 *     paints, and the "Adding to cart…" label is the customer watching them.
 *
 * MEASURED ON STAGING 1.19.191 BEFORE THIS CHANGE, headless Chrome at 1440×900,
 * one real click on the live header control (numbers, not impressions):
 *
 *   - the label swapped to "Adding to cart…" and STAYED there for the full
 *     2,520 ms the harness sampled, without navigating;
 *   - the control's width collapsed 308.88px -> 202.34px, a 106.54px change;
 *   - because `.header-inner` is `justify-content: space-between`, every nav
 *     link slid 53.27px sideways and back.
 *
 * ⭐ THAT 53.27px IS ANDREW'S "shifting of the words on the nav bar goes back
 *    and forth", and it is a direct consequence of a label swap inside a
 *    space-between flex row. An anchor never swaps its label, so the shift
 *    cannot occur — the fix is structural, not a transition or a min-width.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * ⛔ THE /cart/ AND /checkout/ SUPPRESSION IS NOT LOST — IT IS SUBSUMED
 * ─────────────────────────────────────────────────────────────────────────────
 * `bhp_collection_cta_context_allows_add()` existed to stop the header mutating
 * a cart the customer was mid-payment on. That guarantee is now UNCONDITIONAL
 * and strictly stronger: the header cannot mutate a cart on ANY page, because it
 * no longer posts anything anywhere. The function is deliberately KEPT — it is a
 * documented filter (`bhp_collection_cta_context_allows_add`) and other purchase
 * controls may adopt it — but the header no longer consults it, and the test
 * suite asserts the stronger property instead of the narrower one.
 *
 * ⭐ `aria-current="page"` IS RESTORED, and that is a real accessibility repair,
 *    not a side effect. The form variant could not honestly carry it (a button
 *    that posts to /checkout/ does not "point at the page you are on"), so
 *    1.19.183 dropped it sitewide and with it the gold ring
 *    `.header-expedition-cta[aria-current="page"]` draws on /complete-collection/.
 *    The anchor points at that page again, so the assertion is true again.
 */

/**
 * May a purchase-and-checkout control be rendered in the CURRENT request?
 *
 * Separate from `bhp_collection_cta_available()`, which asks whether the
 * machinery exists at all. This asks whether firing it here would be safe.
 *
 * Guarded with `function_exists()` because these are WooCommerce conditionals
 * and the header must render on a site where the plugin is off.
 *
 * @return bool
 */
function bhp_collection_cta_context_allows_add() {
	if ( function_exists( 'is_cart' ) && is_cart() ) {
		return false;
	}
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return false;
	}

	/**
	 * Filter whether the current request may render an add-and-checkout control.
	 *
	 * @param bool $allowed
	 */
	return (bool) apply_filters( 'bhp_collection_cta_context_allows_add', true );
}

/**
 * Render one of the two sitewide header Complete-Collection controls.
 *
 * ⭐ ALWAYS AN ANCHOR TO `/complete-collection/`. Andrew Signore, 2026-08-05 —
 *    reverses same-day item 8 ("Convert to hardcover purchase") on direct UX
 *    experience: the nav routes to the cached collection page; the two-click
 *    purchase lives there. Full ruling and measurements in "THE REVERSAL"
 *    above. RELAYED through the Chief of Staff; not witnessed by this agent.
 *
 * ⛔ THERE IS NO LONGER A CONDITIONAL HERE, AND THAT IS THE POINT. 1.19.183
 *    rendered a purchase form and fell back to this anchor in two cases (plugin
 *    absent, or /cart/ and /checkout/). Andrew's reversal removes the form
 *    entirely, so the anchor is not a fallback any more — it is the control.
 *    A `bhp_collection_cta_available()` check here would now be dead weight
 *    that reads like the form might still return.
 *
 * @param array $args {
 *     @type string $variant 'bar' — the top-right `.header-expedition-cta`,
 *                           visible on desktop/tablet.
 *                           'nav' — the `.site-nav__cta` inside the mobile
 *                           dropdown. Two elements, not one re-shown; see the
 *                           2026-07-14 note in style.css.
 * }
 * @return string HTML.
 */
function bhp_collection_header_cta( array $args = array() ) {
	$args = wp_parse_args( $args, array( 'variant' => 'bar' ) );

	$is_nav = ( 'nav' === $args['variant'] );
	$class  = $is_nav ? 'site-nav__cta' : 'header-expedition-cta';
	$label  = __( 'Get the Complete Collection', 'brave-hearts' );

	/*
	 * `aria-current="page"` is restored with the anchor. It is guarded with
	 * `function_exists()`-free but conditional-tag-safe access: `is_page()` is
	 * core and always present, and on /complete-collection/ the header link
	 * genuinely does point at the current page, so the assertion is true.
	 */
	return sprintf(
		'<a class="%s" href="%s"%s>%s</a>',
		esc_attr( $class ),
		esc_url( home_url( '/complete-collection/' ) ),
		is_page( 'complete-collection' ) ? ' aria-current="page"' : '',
		esc_html( $label )
	);
}
