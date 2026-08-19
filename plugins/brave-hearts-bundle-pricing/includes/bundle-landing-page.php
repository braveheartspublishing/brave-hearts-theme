<?php
/**
 * Brave Hearts Bundle Pricing — premium complete-series landing page.
 *
 * Renders [bhp_complete_series_landing]: the flagship "Three Big
 * Adventures. One Complete Collection." sales page approved in the
 * Staging Refinement Phase 2 mockup (Adventure Bundle4.pdf / .html).
 *
 * Architecture notes:
 * - All prices, savings, and shipping figures are read from
 *   bundle-data.php (bhp_bundle_catalog/_rules/_expected_price/
 *   _single_shipping) — nothing here hardcodes a dollar amount that
 *   isn't already the single source of truth used by the cart/shipping
 *   logic in bundle-cart.php. If Andrew changes a price there, this page
 *   updates automatically instead of silently drifting out of sync.
 * - Both the paperback and hardcover panels of the pricing card (and the
 *   final CTA) are rendered into the page at the same time; JS
 *   (bundle-landing.js) toggles which one is visible via the `hidden`
 *   attribute. No AJAX round-trip and no re-typing of copy is needed to
 *   switch formats, and screen readers/keyboard users get a real native
 *   `hidden` state rather than a CSS-only illusion of one.
 * - The "Add the Complete Set" buttons submit the same form.bhp-bundle-
 *   form markup already intercepted by bundle-drawer.js, using new
 *   complete_paperback_smart / complete_hardcover_smart actions (see
 *   bundle-shortcode.php) so a customer who already has part of the set
 *   in their cart only gets the missing titles added — never a duplicate
 *   line or an unintended quantity bump.
 * - This file never creates a bundle product or bundle SKU. Every Add
 *   Complete Set click adds the three real, individually-mapped
 *   WooCommerce products as separate cart line items.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'bhp_complete_series_landing', 'bhp_bundle_render_landing_page' );

/**
 * Only enqueue this page's dedicated CSS/JS on pages that actually use the
 * shortcode -- the forest-green premium palette is intentionally scoped to
 * this one flagship page, not loaded sitewide.
 */
add_action( 'wp_enqueue_scripts', 'bhp_bundle_landing_enqueue_assets' );
function bhp_bundle_landing_enqueue_assets() {
	if ( ! is_singular() || ! has_shortcode( get_post()->post_content, 'bhp_complete_series_landing' ) ) {
		return;
	}
	/*
	 * F1 (2026-08-03): the Newsreader request is REMOVED. Newsreader was
	 * loaded for this one page in the entire site, which made the Complete
	 * Collection the only surface whose body copy used a typeface found
	 * nowhere else on the walk from home to funnel to collection to product.
	 * The page now takes the sitewide Cormorant Garamond for headings and
	 * EB Garamond for body, both already requested by the theme.
	 */
	wp_enqueue_style( 'bhp-bundle-landing', BHP_BUNDLE_PRICING_URL . 'assets/bundle-landing.css', array(), BHP_BUNDLE_PRICING_VERSION );
	wp_enqueue_script( 'bhp-bundle-landing', BHP_BUNDLE_PRICING_URL . 'assets/bundle-landing.js', array( 'jquery' ), BHP_BUNDLE_PRICING_VERSION, true );
	wp_localize_script(
		'bhp-bundle-landing',
		'bhpLandingData',
		array(
			'savings' => array(
				'paperback' => bhp_bundle_rules( 'paperback' )[3]['discount'],
				'hardcover' => bhp_bundle_rules( 'hardcover' )[3]['discount'],
			),
		)
	);
}

/**
 * Per-format copy that isn't already covered by bundle-data.php's price/
 * discount tables -- material description and CTA/heading strings. Kept
 * in one place so the two format panels stay easy to compare and edit.
 */
function bhp_bundle_landing_format_copy( $format ) {
	if ( 'hardcover' === $format ) {
		return array(
			'title'        => 'Complete Hardcover Collection',
			'subtitle'     => 'Hardcover · sturdy library-quality binding · built to last for years of rereading',
			'cta'          => 'Add the Complete Hardcover Collection',
			'material_tag' => 'Hardcover',
		);
	}
	return array(
		'title'        => 'Complete Paperback Collection',
		'subtitle'     => 'Softcover · durable matte cover · lightweight and easy to read',
		'cta'          => 'Add the Complete Paperback Collection',
		'material_tag' => 'Paperback',
	);
}

/* ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.8.41 (2026-08-14, `CYCLE160-LD-COLLECTION-PRICE-BOX`) — THE
 *     COLLECTION PAGE OPENS ON PAPERBACK. IT IS PAGE-SCOPED ON PURPOSE.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-14, as relayed in the build brief (⛔ RELAYED
 * through `chief-of-staff` (Gandalf); NOT witnessed first-hand by the agent
 * that wrote this file):
 *
 *   "the collection price display goes INSIDE the existing FREE box …
 *    it shows the paperback collection price with a true strikethrough of
 *    the sum-of-singles; and clicking the box's CTA must land the visitor
 *    on the PAPERBACK purchase path (paperback preselected), not hardcover."
 *
 * ⛔ `bhp_bundle_default_format()` IS NOT CHANGED AND MUST NOT BE. It still
 *    returns `'hardcover'` (bundle-data.php, Andrew's 2026-07-30 decision)
 *    and it is read by SIX surfaces outside this page — `inc/book-formats.php`
 *    twice, `inc/collection-cta.php`, `template-parts/commerce/format-cards.php`,
 *    `template-parts/components/complete-collection-feature.php` and
 *    `bundle-shortcode.php`. Flipping it globally would silently re-default
 *    every product page and every collection card on the site, which is a
 *    commercial decision nobody made. The 2026-08-14 instruction names ONE
 *    page, so the override is scoped to ONE page.
 *
 * ⚠ THIS IS NEVERTHELESS A REAL COMMERCIAL MOVEMENT AND IT IS REPORTED AS
 *   ONE: the default add-to-cart from this page goes from the $48.99
 *   hardcover collection to the $31.99 paperback collection. Both figures
 *   are live-verified (staging AND production, 2026-08-14, by WP-CLI). It
 *   is the founder's own instruction, applied as given; it is flagged for
 *   his gate rather than presented as a neutral display change.
 *
 * ⛔ NO PRICE, DISCOUNT, SHIPPING TIER, COUPON, PRODUCT OR VARIATION RECORD
 *    IS TOUCHED BY ANY OF THIS. Only which control starts selected.
 *
 * @return string 'paperback'|'hardcover'
 */
function bhp_bundle_landing_default_format() {
	/**
	 * The format the Complete Collection LANDING PAGE opens on.
	 *
	 * Deliberately separate from `bhp_bundle_default_format()` so this page
	 * can lead with paperback without moving any other surface.
	 *
	 * @param string $format 'paperback'|'hardcover'.
	 */
	$format = (string) apply_filters( 'bhp_bundle_landing_default_format', 'paperback' );
	return 'hardcover' === $format ? 'hardcover' : 'paperback';
}

/**
 * Both formats, the landing page's default first.
 *
 * The page-scoped twin of `bhp_bundle_format_order()`, and it exists for the
 * same reason that one does: the pills, the panels, the price display and the
 * final CTA must all read ONE source for "which format leads", or the selected
 * pill and the visible panel drift apart (`C1`, 2026-08-03).
 *
 * @return string[]
 */
function bhp_bundle_landing_format_order() {
	$default = bhp_bundle_landing_default_format();
	$order   = array( $default, 'hardcover' === $default ? 'paperback' : 'hardcover' );

	/*
	 * ⭐ 1.8.57 (2026-08-18, CYCLE164-LD-PAPERBACK-DEFAULT) — ON A SCHOOL-VISIT
	 *    SESSION THIS PAGE OFFERS PAPERBACK ONLY.
	 *
	 * Andrew Signore, 2026-08-18, verbatim (⛔ RELAYED through the Chief of
	 * Staff; NOT witnessed first-hand by the agent that wrote this):
	 *   "also for the orders on the pre-signed books for the read alouds- based
	 *    on my inventory I can only do paperbacks"
	 *
	 * ⭐ RESTRICTED HERE, IN THE ORDER FUNCTION, RATHER THAN AT THE FOUR CALL
	 *    SITES. This function is already the page's single source for "which
	 *    formats exist and in what order" — the pills at line ~459, the panels
	 *    at ~474, the cold-open block at ~830 and the final CTA panels at ~1735
	 *    all read it. Filtering three of the four is how a hidden pill ends up
	 *    beside a visible panel, which is the exact `C1` defect this function
	 *    was created to end.
	 *
	 * ⛔ THE UI IS NOT THE ENFORCEMENT. `includes/school-visit-paperback-only.php`
	 *    refuses a hardcover at the add-to-cart and at the checkout, on the
	 *    classic AND the Store API paths. This only stops the page offering
	 *    something the server would then refuse.
	 *
	 * ⛔ CONTROL PATH: `bhp_school_visit_paperback_only()` is false for every
	 *    ordinary shopper and this block returns `$order` untouched, so the
	 *    rendered page is byte-identical to 1.8.56.
	 *
	 * ✅ FAILS OPEN: if the predicate is missing, or the intersection somehow
	 *    empties the list, both formats are returned.
	 */
	if ( function_exists( 'bhp_school_visit_paperback_only' ) && bhp_school_visit_paperback_only() ) {
		$restricted = array_values( array_intersect( $order, array( 'paperback' ) ) );
		if ( ! empty( $restricted ) ) {
			return $restricted;
		}
	}

	return $order;
}

/**
 * The three numbers the price display prints, READ FROM WOOCOMMERCE.
 *
 * ⛔ THE STRIKETHROUGH HAS TO BE TRUE OR IT MUST NOT SHIP. A struck-through
 *    former price is an FTC-class claim, so the anchor here is the REAL sum
 *    of buying the three books separately today — `get_price()` on each of
 *    the three live product records — never a higher invented "list price"
 *    and never a hardcoded literal.
 *
 *    VERIFIED LIVE 2026-08-14 on staging AND production by WP-CLI:
 *      paperback  11.99 + 11.99 + 11.99 = 35.97, discount 3.98 -> 31.99
 *      hardcover  17.99 + 17.99 + 17.99 = 53.97, discount 4.98 -> 48.99
 *
 * ⛔ IT FAILS CLOSED, TO THE APPROVED CONSTANT, NOT TO A GUESS. If any of the
 *    three products cannot be resolved or reads a non-positive price, the sum
 *    falls back to `3 * bhp_bundle_expected_price()` — the same sanity-check
 *    figure `bundle-data.php` already documents — rather than publishing a
 *    partial sum, which would understate the strike and turn a true saving
 *    into a false one.
 *
 * ⭐ THE BUNDLE PRICE IS DERIVED, NOT DECLARED. `separate - discount` is
 *    exactly what `bhp_bundle_apply_discount()` charges the cart, so the page
 *    and the cart cannot disagree. If Andrew ever re-prices a single title,
 *    both numbers and the saving follow in the same request.
 *
 * @param string $format 'paperback'|'hardcover'.
 * @return array{separate:float,bundle:float,save:float,live:bool}
 */
function bhp_bundle_landing_price_facts( $format ) {
	$catalog  = bhp_bundle_catalog();
	$discount = (float) bhp_bundle_rules( $format )[3]['discount'];
	$sum      = 0.0;
	$found    = 0;

	if ( isset( $catalog[ $format ] ) && function_exists( 'wc_get_product' ) ) {
		foreach ( $catalog[ $format ] as $info ) {
			$product = wc_get_product( (int) $info['product_id'] );
			if ( ! $product ) {
				continue;
			}
			$price = (float) $product->get_price();
			if ( $price <= 0 ) {
				continue;
			}
			$sum += $price;
			$found++;
		}
	}

	$live = ( 3 === $found );
	if ( ! $live ) {
		$sum = 3 * (float) bhp_bundle_expected_price( $format );
	}

	return array(
		'separate' => $sum,
		'bundle'   => $sum - $discount,
		'save'     => $discount,
		'live'     => $live,
	);
}

/**
 * F14 / CYCLE142-CX-013 / KNOWN_ISSUES CYCLE141-LD-33 — ONE nonce id, not four.
 *
 * `wp_nonce_field()` emits `<input type="hidden" id="bhp_bundle_nonce" ...>`.
 * This page renders FOUR bundle forms (a pricing panel and a final-CTA panel
 * for each of paperback and hardcover, all present in the DOM at once with
 * `hidden` toggling which is visible), so the page carried FOUR elements with
 * the same DOM id. Measured live at both viewports; the count had grown from
 * the three recorded in KNOWN_ISSUES.
 *
 * A duplicate id is invalid HTML, makes `document.getElementById` and every
 * `label[for]` ambiguous, and is exactly the class of defect that makes an
 * accessibility or analytics audit unreliable.
 *
 * The fix is to emit the field WITHOUT an id. Nonce verification reads the
 * request by NAME (`$_POST['bhp_bundle_nonce']`), never by DOM id, so
 * `wp_verify_nonce()` behaves identically -- verified by reading
 * `bhp_bundle_handle_add()`'s own check, which uses the name. The value, the
 * action string and the referer field are all unchanged.
 */
function bhp_bundle_nonce_input() {
	printf(
		'<input type="hidden" name="bhp_bundle_nonce" value="%s" />',
		esc_attr( wp_create_nonce( 'bhp_bundle_add' ) )
	);
	wp_referer_field( true );
}

function bhp_bundle_render_landing_page() {
	ob_start();
	wc_print_notices();
	?>
	<div class="bhp-landing" data-bhp-landing>
		<?php
		bhp_bundle_render_landing_hero();
		bhp_bundle_render_landing_trust_row();
		bhp_bundle_render_landing_parent_outcomes();
		bhp_bundle_render_landing_testimonial();
		bhp_bundle_render_landing_story_section();
		bhp_bundle_render_landing_kirkus();
		bhp_bundle_render_landing_value_comparison();
		bhp_bundle_render_landing_gift_section();
		bhp_bundle_render_landing_final_cta();
		?>
	</div>
	<?php
	bhp_bundle_render_landing_sticky_bar();
	return ob_get_clean();
}

/**
 * Section: concise parent-outcome benefits, placed near the main offer
 * and before the value comparison per the conversion correction spec.
 * Every line is a supportable, non-clinical claim -- no literacy,
 * educational, or guaranteed-performance claims are made.
 */
function bhp_bundle_render_landing_parent_outcomes() {
	?>
	<section class="bhp-landing-outcomes">
		<ul class="bhp-landing-outcomes__list">
			<li>Three complete adventures with familiar recurring characters</li>
			<li>Helps growing readers build confidence across a series</li>
			<li>Combines real-world science, geography, history, courage, and kindness</li>
			<li>Works for family read-alouds and developing independent readers</li>
			<li>Creates a ready-made home, homeschool, or classroom adventure library</li>
		</ul>
	</section>
	<?php
}

/**
 * Sections 1-7 (hero, headline/copy, format selector, pricing card,
 * savings, shipping, main CTA) live together because the format selector
 * and pricing card are one visual unit in the approved mockup.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.8.28 — THE SALES UNIT IS NOW FIRST. CYCLE144-LD-251.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, with screenshots (relayed through the Chief
 * of Staff, quoted not paraphrased): "we need the actual sales section
 * above the fold - these need to switch".
 *
 * ⛔ THIS IS A MOVE, NOT A COPY, AND NOTHING IS DELETED. The two blocks
 *    that already lived inside `.bhp-landing-hero__inner` simply changed
 *    places:
 *
 *      BEFORE   __intro (narrative)  →  __main (gallery + purchase card)
 *      AFTER    __main (gallery + purchase card)  →  __intro (narrative)
 *
 *    Same section element, same inner wrapper, same two child blocks,
 *    same class names, byte-identical copy inside each. Every price row,
 *    the Shipping row, the Activity Book row, the format toggle, the
 *    2-click CTA and the `[data-bhp-pricing-card]` hook are untouched —
 *    they are rendered by the same functions, in the same order, from the
 *    same data.
 *
 * ⛔ WHY TWO HELPER FUNCTIONS RATHER THAN A CUT-AND-PASTE. Reordering by
 *    physically moving 60 lines of markup makes the diff unreviewable and
 *    invites a copy/delete error in exactly the block that carries the
 *    prices. Extracting each block into its own function makes the order
 *    ONE line, and makes a future re-order one line again.
 *
 * The vertical margins that assumed intro-then-main are corrected in
 * `bundle-landing.css` under `.bhp-landing-hero--sales-first`, which is
 * why the section carries that modifier. No other CSS is touched.
 */
function bhp_bundle_render_landing_hero() {
	?>
	<section class="bhp-landing-hero bhp-landing-hero--sales-first">
		<div class="bhp-landing-hero__inner">
			<?php
			bhp_bundle_render_landing_hero_sales();
			bhp_bundle_render_landing_hero_intro();
			?>
		</div>
	</section>
	<?php
}

/**
 * The SALES unit: the book gallery and the purchase card, side by side.
 *
 * Moved verbatim out of bhp_bundle_render_landing_hero() in 1.8.28 so the
 * order of the two hero blocks is a single readable line. Not one
 * character of the markup, the media slot, the fallback covers or the
 * pricing-card call changed in the move.
 */
function bhp_bundle_render_landing_hero_sales() {
	$catalog = bhp_bundle_catalog();
	?>
	<div class="bhp-landing-hero__main">
		<?php
		/*
		 * HERO MEDIA SLOT (2026-08-02). Purely additive.
		 *
		 * The theme's shared product gallery renders here when it has
		 * approved Complete Collection media, so this page uses the same
		 * main-image experience as the individual book pages instead of
		 * a second, parallel design.
		 *
		 * If nothing is hooked — or the theme is swapped — the original
		 * three-cover block below renders exactly as before, so the hero
		 * can never end up without a main image. Cart, pricing,
		 * discount, shipping and coupon logic all live in
		 * bundle-cart.php and are untouched by this.
		 */
		ob_start();
		do_action( 'bhp_bundle_landing_hero_media' );
		$bhp_hero_media = trim( ob_get_clean() );

		if ( '' !== $bhp_hero_media ) {
			echo $bhp_hero_media; // phpcs:ignore WordPress.Security.EscapeOutput -- theme-rendered gallery markup, escaped at source.
		} else {
		?>
		<div class="bhp-landing-hero__covers">
			<?php
			$covers = array(
				array( 'format' => 'paperback', 'title' => 'mariana', 'label' => 'Book One - Ocean' ),
				array( 'format' => 'paperback', 'title' => 'everest', 'label' => 'Book Two - Mountain' ),
				array( 'format' => 'paperback', 'title' => 'amazon', 'label' => 'Book Three - Rainforest' ),
			);
			foreach ( $covers as $cover ) :
				$info = $catalog[ $cover['format'] ][ $cover['title'] ];
				$product = wc_get_product( $info['product_id'] );
				$image_id = $product ? $product->get_image_id() : 0;
			?>
				<figure class="bhp-landing-hero__cover">
					<?php if ( $image_id ) : ?>
						<?php echo wp_get_attachment_image( $image_id, 'medium', false, array( 'loading' => 'eager' ) ); // phpcs:ignore ?>
					<?php endif; ?>
					<figcaption><?php echo esc_html( $cover['label'] ); ?></figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
		<?php
		} // end hero-media fallback
		?>
		<?php bhp_bundle_render_landing_pricing_card(); ?>
	</div>
	<?php
}

/**
 * The NARRATIVE unit: eyebrow, H1 and the travel/ages copy.
 *
 * Moved verbatim out of bhp_bundle_render_landing_hero() in 1.8.28. The
 * copy is locked approved prose and is byte-identical to 1.8.27 — the
 * move changed where it renders, never what it says. The `<h1>` is still
 * the page's only H1 and still carries the same text, so the document
 * outline and the page's headline claim are unchanged.
 */
function bhp_bundle_render_landing_hero_intro() {
	?>
	<div class="bhp-landing-hero__intro">
		<span class="bhp-landing-eyebrow">
			<span class="bhp-landing-eyebrow__dot" aria-hidden="true"></span>
			The Adventures of Charlotte &amp; Henry
		</span>
		<h1 class="bhp-landing-hero__title">Three Big Adventures.<br>One Complete Collection.</h1>
		<p class="bhp-landing-hero__text">Travel from the deepest ocean trench to the top of Mount Everest and into the heart of the Amazon rainforest.</p>
		<p class="bhp-landing-hero__subtext">Adventure chapter books for ages 6&ndash;9 that bring together real places, science, history, courage, and kindness.</p>
	</div>
	<?php
}

function bhp_bundle_render_landing_pricing_card() {
	?>
	<?php
	/*
	 * ⭐ 1.8.28 — THE ANCHOR NOW HAS A TARGET. CYCLE144-LD-252.
	 *
	 * VERIFIED LIVE on production 2026-08-05 before this change: the page
	 * carries `<a href="#bhp-landing-pricing-card">` (gift section) but
	 * `id="bhp-landing-pricing-card"` occurred ZERO times in the rendered
	 * document. The link only worked because `bundle-landing.js`
	 * `initScrollToCard()` intercepts the click; with JS unavailable, or
	 * for a visitor arriving on a `/complete-collection/#bhp-landing-
	 * pricing-card` URL from outside the page, it resolved to nothing.
	 *
	 * Adding the id makes the anchor resolve natively. It changes no
	 * scripted behaviour: the JS handler calls `e.preventDefault()` before
	 * `scrollIntoView()`, so when JS runs the smooth-scroll-and-highlight
	 * path is exactly what it was. `[data-bhp-pricing-card]` is unchanged
	 * and is still what the JS and the sticky-bar suppression query on.
	 *
	 * The id is unique on the page — the pricing card renders once.
	 */
	?>
	<div class="bhp-landing-card" id="bhp-landing-pricing-card" data-bhp-pricing-card>
		<span class="bhp-landing-card__badge">
			<span class="bhp-landing-eyebrow__dot" aria-hidden="true"></span>
			Best Value - Get the Complete Collection
		</span>

		<?php bhp_bundle_render_landing_cold_open(); ?>

		<?php /* C1 — same ordering call. These panels are toggled by `hidden`, so
		         this is a DOM/tab-order alignment with the pills below, not a
		         visual change: the visible panel is the default either way. */ ?>
		<?php foreach ( bhp_bundle_landing_format_order() as $format ) : ?>
			<?php bhp_bundle_render_landing_pricing_panel( $format ); ?>
		<?php endforeach; ?>

		<div class="bhp-landing-format-selector" role="radiogroup" aria-label="Choose your format">
			<p class="bhp-landing-format-selector__label">Choose Your Format</p>
			<div class="bhp-landing-format-selector__options">
				<?php
				/*
				 * C1 (2026-08-03) — the pills now lead with the format the page
				 * actually opens on. `bhp_bundle_format_order()` is derived from
				 * `bhp_bundle_default_format()`, so the selected pill is always
				 * the first one. Order only; no price, product or default
				 * SELECTION changes. See bundle-data.php for the full note.
				 */
				foreach ( bhp_bundle_landing_format_order() as $format ) :
					$price = bhp_bundle_rules( $format )[3]['discount'];
					$bundle_price = ( 3 * bhp_bundle_expected_price( $format ) ) - bhp_bundle_rules( $format )[3]['discount'];
					// 2026-07-30: the initially-selected format now comes from
					// bhp_bundle_default_format() instead of being hardcoded to
					// paperback, so the selector, the pricing panel and the final
					// CTA panel below can never disagree about the default.
					$is_default = bhp_bundle_landing_default_format() === $format;
				?>
				<button
					type="button"
					role="radio"
					aria-checked="<?php echo $is_default ? 'true' : 'false'; ?>"
					class="bhp-landing-format-btn<?php echo $is_default ? ' is-selected' : ''; ?>"
					data-bhp-format-btn="<?php echo esc_attr( $format ); ?>"
				>
					<span class="bhp-landing-format-btn__name"><?php echo esc_html( ucfirst( $format ) ); ?></span>
					<span class="bhp-landing-format-btn__price">$<?php echo esc_html( number_format( $bundle_price, 2 ) ); ?> collection</span>
					<span class="bhp-landing-format-btn__check" aria-hidden="true">&#10003; Selected</span>
				</button>
				<?php endforeach; ?>
			</div>
			<?php
			/*
			 * ⭐ 1.8.57 (2026-08-18): the helper sentence has to follow the pills.
			 *    With hardcover withheld from a school-visit session, "or
			 *    hardcover for a more durable, gift-ready edition" points at a
			 *    control that is not on the page, which reads as a broken page
			 *    rather than as a decision.
			 *
			 * ⛔ §9.1 VOICE on the replacement string: I/me, never "we". ⛔ NO EM
			 *    DASH. The replacement is the plugin's shared note, beside the
			 *    refusal message it belongs with, so a copy change is one file.
			 *
			 * ⛔ CONTROL PATH: `bhp_school_visit_paperback_only_note()` returns ''
			 *    for every ordinary shopper, so the original sentence is printed
			 *    byte-identically to 1.8.56.
			 */
			$bhp_landing_pb_note = function_exists( 'bhp_school_visit_paperback_only_note' )
				? bhp_school_visit_paperback_only_note()
				: '';
			?>
			<?php if ( '' !== $bhp_landing_pb_note ) : ?>
			<p class="bhp-landing-format-selector__note"><?php echo esc_html( $bhp_landing_pb_note ); ?></p>
			<?php else : ?>
			<p class="bhp-landing-format-selector__note">Choose paperback for the most affordable reading set, or hardcover for a more durable, gift-ready edition.</p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.8.32 — THE COLD-TRAFFIC OPENING. CYCLE147-LD-01.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-07, from his own mobile audit of this page
 * (⛔ RELAYED through the Chief of Staff; NOT witnessed first-hand here).
 * A visitor arriving cold met a price box before a reason, and the page's
 * only emotional argument sat below it.
 *
 * This block is the reason, and it renders between the "Best Value" badge
 * and the purchase panel: headline → one-line subhead → compact trust bar.
 *
 * ⛔ EVERY CLAIM IN THE TRUST BAR IS ALREADY PUBLISHED ON THIS PAGE. It
 *    invents nothing and it widens nothing:
 *
 *    · The Kirkus line is a verbatim FRAGMENT of the approved quote in
 *      `bhp_get_kirkus_review_data()`, which this page already renders in
 *      full further down, and it carries the reviewed title — because only
 *      The Mariana Trench was reviewed and this page sells three books.
 *      Dropping that scope would turn a true claim into a false one.
 *    · The teacher line is a verbatim FRAGMENT of the testimonial this page
 *      already renders in `bhp_bundle_render_landing_testimonial()`, with
 *      its existing attribution kept.
 *    · "Placed in classrooms across Boise" is the EXACT string the trust
 *      row below already publishes. Its whole history — why the verb is
 *      "placed" and not "used", and why the count was dropped — is in the
 *      docblock on `bhp_bundle_render_landing_trust_row()` and is unchanged.
 *      Placement remains the only thing asserted.
 *
 * ⛔ BOTH FRAGMENTS ARE ASSERTED TO BE SUBSTRINGS OF THEIR SOURCES by
 *    `tests/test-collection-cold-traffic.php`, so a future edit to either
 *    source that leaves a fragment behind fails a suite instead of quietly
 *    publishing a quote nobody said.
 *
 * ⛔ NO rating expression, no count, no aggregate, no "trusted by", no
 *    reading, literacy, classroom or developmental outcome.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.8.33 (2026-08-07) — ANDREW'S REVISION LIST. CYCLE147-LD-06..09.
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ RELAYED through the Chief of Staff; NOT witnessed first-hand here.
 *
 * 1. THE SUBHEAD. "Short chapters · real places · ages 6–9" repeated the
 *    headline directly above it, which already says both. Replaced with the
 *    canonical brand promise — "Educational without feeling like homework"
 *    is the company's own core-promise wording (`C:\BHP\CLAUDE.md`), so it
 *    is claim-safe by construction: it describes the reading experience and
 *    asserts no outcome, no measure and no third-party endorsement.
 *
 * 2. THE BOISE LINE IS GONE FROM THIS BAR. Andrew's instruction, and it
 *    also buys back a line above the phone fold. ⚠ IT IS NOT DELETED FROM
 *    THE PAGE: `bhp_bundle_render_landing_trust_row()` still publishes it,
 *    unchanged, with its whole "placed, not used" history intact. His
 *    instruction named the trust BAR (this block); the trust ROW further
 *    down was not in scope and was deliberately left alone.
 *
 * 3. ⭐ THE FIVE-STAR LINE, ABOVE AND BELOW THE HEADLINE BLOCK.
 *
 *    ⛔ VERIFIED BEFORE BUILDING, NOT ASSUMED. `inc/amazon-reviews.php`
 *       holds SIX approved reviews and EVERY ONE carries `'rating' => 5`
 *       (four The Mariana Trench, two Mount Everest, ZERO The Amazon).
 *       The claim is therefore recorded fact, not invention.
 *
 *    ⛔ IT IS GATED ON THAT DATA, NOT TYPED IN. It renders only when
 *       `bhp_bundle_landing_five_star_reviews_exist()` finds approved
 *       reviews AND every one of them is a 5. Downgrade a review in the
 *       registry and the line removes itself; it does not have to be
 *       remembered.
 *
 *    ⚠ ONE SCOPE TENSION IS RECORDED HERE RATHER THAN RESOLVED. On
 *      2026-08-02 the trust-row badge was deliberately narrowed from the
 *      unqualified "Five-star reader reviews" to "…on our first two
 *      titles", because this page sells three books and The Amazon has
 *      none. Andrew's 2026-08-07 instruction specifies the unqualified
 *      wording "5-Star Reader Reviews" and is the later instruction, so it
 *      is what shipped. The earlier narrowing is UNCHANGED where it lives.
 *      Flagged for the decisions register; NOT silently reconciled in
 *      either direction, in either direction's favour.
 */
function bhp_bundle_landing_five_star_reviews_exist() {
	if ( ! function_exists( 'bhp_get_approved_amazon_reviews_for_book' ) ) {
		return false;
	}
	$found = 0;
	foreach ( array( 'mariana_trench', 'mount_everest', 'amazon_rainforest' ) as $slug ) {
		foreach ( (array) bhp_get_approved_amazon_reviews_for_book( $slug ) as $review ) {
			if ( 5 !== (int) ( $review['rating'] ?? 0 ) ) {
				return false; // one non-five and the claim stops being true as stated
			}
			$found++;
		}
	}
	return $found > 0;
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.8.42 (2026-08-14, `CYCLE160-LD-COLLECTION-PRICE-BOX` ITERATION 2)
 *     — THE KIRKUS FRAGMENT LEAVES THIS BOX. THE FREE BULLETS COME UP.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-14, VERBATIM as relayed in the iteration-2 brief
 * (⛔ RELAYED through `chief-of-staff`; NOT witnessed first-hand here):
 * "i think we remove the kirkus review and bring up the FREEx3 - make the
 * price visible and make sure the CTA is above the url shadow that covers
 * the bottom of the fold."
 *
 * ⛔ THIS IS A REMOVAL FROM ONE BOX, NOT FROM THE PAGE, AND THE DIFFERENCE
 *    IS THE WHOLE POINT. `bhp_bundle_render_landing_kirkus()` still renders
 *    the FULL approved Kirkus quote, its attribution, the reviewed title and
 *    the review link further down this same page, from the same
 *    `bhp_get_kirkus_review_data()` source, byte-unchanged. NOTHING FACTUAL
 *    LEAVES THE PAGE — a fragment of a quote stops being repeated above a
 *    quote that is published in full 3,000px below it.
 *    `tests/test-collection-cold-traffic.php` §3 asserts BOTH halves: absent
 *    from the cold-open block, present in the Kirkus section.
 *
 * ⛔ WHY IT WAS THE LINE THAT COULD GO, AND WHY THAT WAS ANDREW'S CALL AND
 *    NOT THIS FILE'S. MEASURED on staging 1.19.226/1.8.41, fresh profile,
 *    `window.innerWidth` asserted, 2026-08-14:
 *
 *      390x844   fragment y 557-595 · next line starts 603  → 46px
 *      360x740   fragment y 555-593 · next line starts 601  → 46px
 *
 *    46px is the single largest reclaimable block above the buy button that
 *    is not a price element, not the five-star line and not a FREE bullet.
 *    Iteration 1 quantified it and did NOT spend it, because spending it is
 *    a copy decision. The founder then ruled. This implements his ruling; it
 *    did not make it.
 *
 * ⭐ "BRING UP THE FREEx3" IS SATISFIED BY THE REMOVAL ITSELF. The FREE
 *    bullets were already the 2nd-4th items of this list; deleting the 1st
 *    makes them the 1st-3rd and lifts them 46px. No bullet is reordered, no
 *    bullet is reworded, and all three live gates — the shipping tier, the
 *    activity-book predicate and the vocabulary-cards predicate — are
 *    byte-untouched, so an environment where an offer is not live still drops
 *    that bullet by itself.
 *
 *    ⚠ THE GATE FUNCTIONS ARE NAMED IN PROSE HERE, WITHOUT THEIR TRAILING
 *      PARENTHESES, AND THAT IS DELIBERATE RATHER THAN SLOPPY.
 *      `test-addon-vocab-cards.php` §9 counts occurrences of the vocabulary
 *      predicate's name-plus-parentheses in this file's RAW SOURCE TEXT and
 *      requires exactly 2 — a count that cannot tell a call site from a
 *      comment. The first draft of this docblock wrote it in the usual
 *      backticked style, took the count to 3, and turned that suite red on a
 *      build whose code was correct. ⛔ The CODE is right and the TEST's
 *      needle is loose. The loose needle is REPORTED to its owner rather
 *      than tightened from here; this comment simply stops tripping it.
 *
 * ⚠ THE `<ul>` NOW HOLDS ONLY FREE BULLETS, and two rules elsewhere were
 *   written for a mixed list. Both were designed to self-retire and both do:
 *     · `li:not(.__free) + li.__free { margin-top: .5rem }` (1.8.40) has no
 *       match left and contributes 0px, exactly as its own comment predicted
 *       ("if the Kirkus fragment is ever ungated the rule disappears with it
 *       instead of leaving a stray 8px hole").
 *     · the `__free`-scoped 15px/800 sizing (1.8.39) is unchanged and still
 *       deliberately class-scoped rather than moved onto the `<ul>`.
 *   ⛔ NEITHER IS DELETED. Restoring the fragment restores both behaviours
 *      with no second edit.
 */
function bhp_bundle_render_landing_cold_open() {
	$five_star = bhp_bundle_landing_five_star_reviews_exist();
	?>
	<div class="bhp-landing-coldopen">
		<?php if ( $five_star ) : ?>
			<p class="bhp-landing-coldopen__stars"><span class="bhp-landing-coldopen__stars-glyphs" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span> 5-Star Reader Reviews</p>
		<?php endif; ?>
		<?php
		// 2026-08-07 revision (Andrew, current-turn): headline split onto two
		// lines, and the duplicate below-headline star line removed — the star
		// line now renders once, above the headline only.
		?>
		<h2 class="bhp-landing-coldopen__headline">Real places. Short chapters.<br />Stories that pull kids off screens.</h2>
		<p class="bhp-landing-coldopen__subhead">Educational without feeling like homework &middot; ages 6&ndash;9</p>
		<?php
		// 2026-08-07 revision (Andrew, current-turn): the teacher quote leaves
		// the cold-open bar (still published in full further down the page);
		// the FREE offer line takes its place, FREE bold per his standing rule.
		?>
		<ul class="bhp-landing-coldopen__trust">
			<?php
			/*
			 * ⛔⭐ 1.8.42 — THE KIRKUS FRAGMENT WAS THE FIRST ITEM OF THIS LIST
			 *     AND IS REMOVED ON ANDREW SIGNORE'S 2026-08-14 INSTRUCTION.
			 *     The superseded markup, preserved verbatim so the movement is
			 *     visible and is not re-derived:
			 *
			 *   <li>&ldquo;&hellip;spark children&rsquo;s curiosity&rdquo;
			 *   &mdash; <?php echo esc_html( $kirkus['attribution'] ); ?>,
			 *   on <em>The Mariana Trench</em></li>
			 *
			 *     …together with the `$kirkus = bhp_get_kirkus_review_data();`
			 *     lookup at the top of this function, which had no other reader.
			 *
			 * ⛔ IT IS STILL PUBLISHED ON THIS PAGE, in full, with its
			 *    attribution, its reviewed title and its link, by
			 *    `bhp_bundle_render_landing_kirkus()` — same source function,
			 *    byte-unchanged. See this function's docblock for the measured
			 *    46px the removal buys and for why it was the founder's call.
			 */
			?>
			<?php
			/*
			 * ═══════════════════════════════════════════════════════════════
			 * ⭐⭐ 1.8.36 (2026-08-09, CYCLE148-LD-06) — TWO DEFECTS FIXED IN
			 *     ONE LINE, and the second was not in the brief.
			 * ═══════════════════════════════════════════════════════════════
			 *
			 * The superseded markup, preserved so the movement is visible:
			 *
			 *   <li><strong>FREE</strong> Activity Book + <strong>FREE</strong> Shipping</li>
			 *
			 * ⛔ DEFECT 1 — IT IS A COMBINED SENTENCE, which is the exact
			 *    shape Andrew Signore's 2026-08-06 ruling forbids (relayed,
			 *    NOT witnessed first-hand): "bold, each free item its own
			 *    bullet line, NEVER COMBINED SENTENCES." Two free items
			 *    welded together with a "+" on one line is one bullet doing
			 *    the work of two, and it is why the second one reads as an
			 *    afterthought.
			 *
			 * ⛔ DEFECT 2 — IT WAS HARDCODED AND UNGATED, and this one was
			 *    found rather than briefed. Every other free-offer surface in
			 *    this plugin and in the theme asks whether the offer is
			 *    actually live on THIS environment before saying the word;
			 *    this line asserted both promises unconditionally. On an
			 *    environment where `BHP-ACTIVITY-BOOK-01` stops resolving —
			 *    which is precisely the state production was in before the
			 *    product existed — the highest, coldest line on the
			 *    highest-traffic purchase page would have promised a free
			 *    book the cart then charges for. It now routes through the
			 *    same gated helper as everything else and disappears with no
			 *    copy edit.
			 *
			 * ⭐ AND IT NOW CARRIES THE SAVINGS. Andrew, same week: "say its
			 *    a $5.00 savings". The figure is WooCommerce's own, never a
			 *    literal.
			 *
			 * ⛔ BUILT FROM PLUGIN-NATIVE CALLS ONLY. The theme has an
			 *    equivalent helper (`bhp_book_free_bullet_lines()`) and it is
			 *    deliberately NOT used here: the dependency in this codebase
			 *    runs theme -> plugin, never the reverse, because the plugin
			 *    has to keep working under a theme that does not define it.
			 *    Both helpers read the same two live predicates, so they
			 *    cannot disagree about whether an item is free.
			 */
			$bhp_coldopen_free = array();
			$bhp_coldopen_ship = bhp_bundle_rules( bhp_bundle_landing_default_format() );
			if ( isset( $bhp_coldopen_ship[3]['shipping'] ) && 0.0 === (float) $bhp_coldopen_ship[3]['shipping'] ) {
				/*
				 * ⭐ 1.8.52 (`CYCLE163-LD-PICKUP-NATIVE`) — THE SCHOOL-VISIT SWAP.
				 *    A parent who arrived from a school's pre-visit link is not
				 *    being shipped anything, so the word must not appear on the
				 *    page they buy from. The claim is unchanged in substance and
				 *    is still gated on the same live shipping predicate above;
				 *    only the framing moves, and only for a flagged session.
				 *    Same swap, same helper, at the closing CTA further down.
				 */
				$bhp_coldopen_free[] = bhp_school_visit_use_delivery_framing()
					? bhp_school_visit_delivery_bullet()
					: 'FREE Shipping on the complete collection';
			}
			if ( function_exists( 'bhp_bundle_addon_free_with_collection' )
				&& bhp_bundle_addon_free_with_collection()
				&& function_exists( 'bhp_bundle_addon_free_offer_line' ) ) {
				$bhp_coldopen_free[] = bhp_bundle_addon_free_offer_line();
			}
			/*
			 * ⭐ 1.8.38 - THE THIRD BULLET. Andrew's vocabulary-cards ruling
			 *    (RELAYED, ⛔ not witnessed first-hand) adds "FREE Vocabulary
			 *    Card Activity" wherever the other two FREE bullets render.
			 *    Order is Shipping, Activity Book, Vocabulary Cards, achieved
			 *    by append. Gated on its own live predicate, never inferred
			 *    from the activity book's - though the predicate itself
			 *    defers to it, because the cards ride on that grant.
			 */
			if ( function_exists( 'bhp_bundle_vocab_cards_live' )
				&& bhp_bundle_vocab_cards_live() ) {
				$bhp_coldopen_free[] = bhp_bundle_vocab_free_offer_line();
			}
			?>
			<?php
			/*
			 * ═══════════════════════════════════════════════════════════
			 * ⭐ 1.8.39 (2026-08-12, `CYCLE154-LD-COLLECTION-TRIM`) — THE
			 *    FREE BULLETS GET A CLASS, SO THEY CAN BE SIZED LIKE EVERY
			 *    OTHER FREE BULLET ON THE SITE.
			 * ═══════════════════════════════════════════════════════════
			 *
			 * Andrew Signore, 2026-08-11/12, VERBATIM as relayed in the
			 * brief (⛔ RELAYED through Gandalf; NOT witnessed here):
			 * "the FREE lines need to be a bit bigger as well they are tiny
			 * on mobile lets match it".
			 *
			 * The superseded markup, preserved so the movement is visible:
			 *
			 *   <li><strong>{line}</strong></li>
			 *
			 * ⛔ THE CLASS IS ON THE FREE LINES ONLY, NOT ON THE `<ul>`, and
			 *    that is the whole reason it exists rather than a rule on
			 *    `.bhp-landing-coldopen__trust li`. The first item in this
			 *    list is the Kirkus review fragment — a quiet attribution in
			 *    a different claim class, with its own italics and its own
			 *    source. Andrew named "the FREE lines"; the standing
			 *    treatment being matched (`.bhp-free-bullets__item`, theme
			 *    `style.css`) is likewise the FREE-item treatment defined by
			 *    his 2026-08-06 ruling. Sizing the review fragment up to a
			 *    15px/800 offer bullet would restyle copy nobody asked to
			 *    restyle and would cost the mobile fold three more lines.
			 *
			 * ⛔ THE STRING IS UNTOUCHED. No word, no capital and no gate
			 *    changes here — `$bhp_coldopen_free` is built exactly as
			 *    1.8.38 built it, from the same three live predicates, in the
			 *    same order. This adds one attribute.
			 */
			?>
			<?php foreach ( $bhp_coldopen_free as $bhp_coldopen_line ) : ?>
				<li class="bhp-landing-coldopen__free"><strong><?php echo esc_html( $bhp_coldopen_line ); ?></strong></li>
			<?php endforeach; ?>
		</ul>
		<?php
		/*
		 * ⭐ 1.8.41 — THE PRICE, IN THE BOTTOM-RIGHT OF THIS BOX. One block per
		 *    format; the non-default one carries `hidden` and is swapped by the
		 *    EXISTING `setFormat()` in `bundle-landing.js`, which already
		 *    toggles every `[data-bhp-format-panel]` on the page. No new
		 *    toggling mechanism, no second definition of "the selected format".
		 */
		foreach ( bhp_bundle_landing_format_order() as $bhp_coldopen_fmt ) {
			bhp_bundle_render_landing_coldopen_price( $bhp_coldopen_fmt );
		}
		?>
	</div>
	<?php
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.8.41 (2026-08-14, `CYCLE160-LD-COLLECTION-PRICE-BOX`) — THE PRICE
 *     MOVES INTO THE FREE BOX, BOTTOM-RIGHT, ABOVE THE FOLD.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-14, as relayed in the build brief (⛔ RELAYED
 * through `chief-of-staff`; NOT witnessed first-hand here): the collection
 * price display goes INSIDE the existing FREE box, bottom-right region,
 * above the fold on desktop AND mobile, paperback-first, with a TRUE
 * strikethrough of the sum-of-singles.
 *
 * ⛔ WHY IT HAD TO MOVE, MEASURED RATHER THAN ASSUMED. On staging 1.19.225 /
 *    1.8.40, 390x844, `window.innerWidth` asserted 390, fresh profile, the
 *    old price row rendered at y 767-819 — and the WPConsent banner occupies
 *    y 732-844 on a first visit (it lives in an open shadow root on a 0x0
 *    host; a light-DOM query misses it, the same trap 1.8.33 and 1.8.40 both
 *    record). The price was therefore ENTIRELY BEHIND the consent banner for
 *    every first-time mobile visitor, which is exactly the complaint. The new
 *    block renders at y 681-711 — above the banner and above the fold.
 *
 * ⛔ EVERY NUMBER IS DERIVED, NONE IS TYPED IN. See
 *    `bhp_bundle_landing_price_facts()`. There is no `$31.99`, `$35.97`,
 *    `$48.99` or `$53.97` literal anywhere in this function, and
 *    `tests/test-collection-price-box.php` asserts that there is not.
 *
 * ⭐ THE VISIBLE SAVINGS PHRASE IS "Save $X", NOT "save $X buying together",
 *    AND THAT TRIM IS REPORTED RATHER THAN QUIET. The brief's example wording
 *    was "e.g."; the longer phrase MEASURED 45px tall at 390 and 360 (it
 *    wraps) against 25px for the short one, and every pixel above the buy
 *    button is spent against the 732px consent-banner ceiling described
 *    above. The "buying together" meaning is not dropped — it is carried by
 *    the struck sum-of-singles standing immediately beside the bundle price,
 *    and stated in full in the visually-hidden sentence below, which is what
 *    a screen reader announces.
 *
 * ⛔ THE STRIKE IS A REAL `<s>`, NOT `text-decoration` ON A BARE SPAN, so the
 *    "this is a former/comparison price" semantic survives CSS being off and
 *    is present in the accessibility tree rather than only in the paint.
 *
 * ⛔ THE ALTERNATE-FORMAT LINE IS QUIET AND CARRIES NO PRICE. Andrew: "a
 *    quieter 'hardcover available' line (do NOT lead with $48.99)". The
 *    format pills below the box already print both collection prices; this
 *    line states availability only.
 *
 * @param string $format 'paperback'|'hardcover'.
 * @return void
 */
function bhp_bundle_render_landing_coldopen_price( $format ) {
	$facts   = bhp_bundle_landing_price_facts( $format );
	$other   = 'hardcover' === $format ? 'paperback' : 'hardcover';
	$is_open = bhp_bundle_landing_default_format() === $format;

	$separate = '$' . number_format( $facts['separate'], 2 );
	$bundle   = '$' . number_format( $facts['bundle'], 2 );
	$save     = '$' . number_format( $facts['save'], 2 );
	?>
	<p class="bhp-landing-coldopen__price" data-bhp-format-panel="<?php echo esc_attr( $format ); ?>" <?php echo $is_open ? '' : 'hidden'; ?>>
		<span class="screen-reader-text">
			<?php
			printf(
				/* translators: 1: format, 2: sum of the three books bought separately, 3: collection price, 4: amount saved */
				esc_html__( 'The three %1$s books bought separately cost %2$s. The Complete Collection is %3$s, so you save %4$s buying them together.', 'bhp-bundle-pricing' ),
				esc_html( $format ),
				esc_html( $separate ),
				esc_html( $bundle ),
				esc_html( $save )
			);
			?>
		</span>
		<s class="bhp-landing-coldopen__price-was" aria-hidden="true"><?php echo esc_html( $separate ); ?></s>
		<span class="bhp-landing-coldopen__price-now" aria-hidden="true"><?php
			// 2026-08-14 Andrew: clean (sans) font for the price, cents smaller.
			// $bundle is always a derived "$NN.NN" string (never hardcoded); split
			// dollars/cents for display only — screen readers still get the full
			// price from the visually-hidden sentence above, and this span stays
			// aria-hidden as before. Falls back to the whole string un-split if
			// the format is ever not D.DD.
			if ( preg_match( '/^(.*)\.(\d{2})$/', $bundle, $m ) ) {
				echo esc_html( $m[1] ) . '<span class="bhp-landing-coldopen__price-cents">.' . esc_html( $m[2] ) . '</span>';
			} else {
				echo esc_html( $bundle );
			}
		?></span>
		<span class="bhp-landing-coldopen__price-save" aria-hidden="true"><?php echo esc_html( 'Save ' . $save ); ?></span>
		<?php
		/*
		 * ⭐ 1.8.57 (2026-08-18): "Hardcover available" is a CLAIM, and on a
		 *    school-visit session it is not a true one. The button also switches
		 *    the page to a format panel that no longer exists there, so leaving
		 *    it would produce a dead control on the highest-value commerce page
		 *    on the site.
		 *
		 * ⛔ CONTROL PATH: `bhp_school_visit_paperback_only()` is false for every
		 *    ordinary shopper and this line renders byte-identically to 1.8.56.
		 */
		$bhp_alt_offerable = ! function_exists( 'bhp_school_visit_paperback_only' ) || ! bhp_school_visit_paperback_only();
		?>
		<?php if ( $bhp_alt_offerable ) : ?>
		<span class="bhp-landing-coldopen__price-alt">
			<span aria-hidden="true">&middot;</span>
			<button type="button" class="bhp-landing-coldopen__price-altbtn" data-bhp-format-link="<?php echo esc_attr( $other ); ?>">
				<?php echo esc_html( ucfirst( $other ) . ' available' ); ?>
			</button>
		</span>
		<?php endif; ?>
	</p>
	<?php
}

/**
 * The full approved testimonial, in ONE place.
 *
 * Extracted in 1.8.32 for a single reason: the cold-open trust bar quotes a
 * two-word fragment of it, and a fragment whose source is a string literal
 * buried in a render function is a fragment nobody can check. Now the test
 * suite can assert the fragment against the source. The wording is
 * byte-identical to what `bhp_bundle_render_landing_testimonial()` published
 * before this change.
 */
function bhp_bundle_landing_testimonial_quote() {
	return 'My students were drawn to the vivid setting and sense of exploration. It&rsquo;s engaging, educational, and a great addition to any classroom or home library.';
}

/**
 * ⭐ 1.8.32 — THE PRIMARY CTA LEADS WITH THE BENEFIT, AND ONLY WHEN IT IS TRUE.
 *
 * Andrew's instruction was a benefit-driven label: "Get the Complete
 * Collection – Free Shipping".
 *
 * ⛔ THE SHIPPING HALF IS GATED, not typed in. It is appended only when the
 *    three-book rule for THIS format actually resolves to a free tier, read
 *    from `bhp_bundle_rules()` through `bhp_bundle_shipping_is_free()` — the
 *    predicate that lives beside `bhp_bundle_shipping_display()` precisely so
 *    the label and the threshold can never disagree. If Andrew ever re-prices
 *    collection shipping, this label stops making the claim by itself; it does
 *    not have to be remembered.
 *
 *    ⚠ The predicate is used rather than string-matching the rendered wording,
 *      because that wording is filterable (`bhp_bundle_shipping_display`) and a
 *      label that decided a truth claim by comparing against filtered prose
 *      would silently start lying the first time anyone used the filter.
 *
 * The format is deliberately NOT in the label: the label sits above the
 * price block and the format selector, and naming a format there would
 * assert a choice the visitor has not made yet. The panel subtitle, the
 * selector and the final CTA all still name it.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.8.39 (2026-08-12, `CYCLE154-LD-COLLECTION-TRIM`) — THE SHIPPING HALF
 *    COMES OFF THE BUTTON. "SHORTEN B."
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-11/12, on the reviewed options for this page:
 * "I agree with all changes" — of which B was, verbatim in the brief,
 * `GET THE COMPLETE COLLECTION – FREE SHIPPING` → `GET THE COMPLETE
 * COLLECTION`. ⛔ RELAYED through `chief-of-staff` (Gandalf); NOT
 * witnessed first-hand by this agent.
 *
 * The superseded body, preserved verbatim so the movement is visible:
 *
 *   $rule  = bhp_bundle_rules( $format )[3];
 *   $label = 'Get the Complete Collection';
 *   if ( bhp_bundle_shipping_is_free( $rule['shipping'] ) ) {
 *       $label .= ' – Free Shipping';
 *   }
 *   return $label;
 *
 * ⛔ NO CLAIM IS LOST, AND THAT IS THE ONLY REASON THIS IS SAFE TO CUT.
 *    Free shipping is still stated twice on this page, in the two places
 *    a visitor actually reads it: the cold-open bullet list at the very
 *    top (`bhp_bundle_render_landing_cold_open()`), and the bullet list
 *    under the closing CTA (`.bhp-landing-final__free`). Both are GATED on
 *    the same `bhp_bundle_shipping_is_free()` / `bhp_bundle_rules()`
 *    predicate this function used, so the page still stops making the
 *    claim by itself the day collection shipping is re-priced. Measured on
 *    staging at 390px with `window.innerWidth` asserted before the cut: the
 *    words FREE SHIPPING appeared THREE times above the fold — the
 *    cold-open bullet, this button, and the price-box row that 1.8.39 also
 *    removes. The button was the middle of three.
 *
 * ⭐ IT ALSO KILLS THE LAST NON-RANGE EN DASH ON THE PAGE. The rendered
 *    document at 390px carried five `–` characters; four are the `ages 6–9`
 *    range, which is correct typography and is left alone. The fifth was
 *    this label's separator.
 *
 * ⛔ THE `$format` PARAMETER IS KEPT DELIBERATELY. Every caller and the two
 *    test suites pass it, and the label is still a per-format lookup as far
 *    as the interface is concerned. Dropping it would be a signature change
 *    for a copy edit, and it would remove the seam a future format-specific
 *    label needs. It is simply not consulted while the label is a constant.
 */
function bhp_bundle_landing_primary_cta_label( $format ) {
	unset( $format ); // see the note above: kept in the signature on purpose.
	return 'Get the Complete Collection';
}

/**
 * The URL of the Refund and Returns Policy page, resolved LIVE.
 *
 * ⛔ NO PATH LITERAL IS TRUSTED FIRST. WooCommerce owns this page through
 *    `woocommerce_refund_returns_page_id`, which is what the store actually
 *    installed and what an editor changing the page keeps in sync. The slug
 *    lookup is the second try, and the hardcoded path is only a last resort
 *    so the badge is never rendered with an empty `href`.
 *
 * VERIFIED LIVE 2026-08-14 by WP-CLI on staging:
 *   `wp option get woocommerce_refund_returns_page_id` -> 10
 *   `get_permalink(10)` -> https://staging2.braveheartspublishing.com/refund_returns/
 * and on production (read-only) `get_page_by_path('refund_returns')` -> ID 10.
 *
 * @return string
 */
function bhp_bundle_guarantee_policy_url() {
	$page_id = 0;

	if ( function_exists( 'wc_get_page_id' ) ) {
		$page_id = (int) get_option( 'woocommerce_refund_returns_page_id', 0 );
	}
	if ( $page_id <= 0 ) {
		$page = get_page_by_path( 'refund_returns' );
		$page_id = $page ? (int) $page->ID : 0;
	}
	if ( $page_id > 0 && 'publish' === get_post_status( $page_id ) ) {
		$permalink = get_permalink( $page_id );
		if ( $permalink ) {
			return $permalink;
		}
	}

	return home_url( '/refund_returns/' );
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.8.46 (2026-08-14, `CYCLE161-LD-TYP-AND-GUARANTEE`) — THE 30-DAY
 *     GUARANTEE BADGE, DIRECTLY BELOW THE BUY BUTTON.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE SOURCE OF THE CLAIM IS THE LIVE POLICY PAGE, AND IT WAS READ, NOT
 *    REMEMBERED. Verified by WP-CLI on PRODUCTION 2026-08-14 (read-only;
 *    `get_page_by_path('refund_returns')`, `post_modified 2026-08-14
 *    12:55:42`). The policy section reads, VERBATIM:
 *
 *      "Our 30-Day Guarantee
 *       If the books don't fit your reader, contact us within 30 days of
 *       delivery and we will refund your purchase in full to your original
 *       payment method. Because every book is printed on demand, there is
 *       nothing to send back - keep the books or pass them along. This
 *       guarantee applies to books purchased directly from
 *       BraveHeartsPublishing.com. Damaged, defective, incorrect, or
 *       missing orders are handled as described above, as always."
 *
 * ⚠⚠ TWO DEVIATIONS FROM THE BRIEF'S SHORT FORM ARE REPORTED, NOT HIDDEN:
 *
 *    1. THE BRIEF SAID "tell me within 30 days". THE POLICY SAYS "within 30
 *       days OF DELIVERY". The two words are restored here because dropping
 *       the anchor changes when the clock starts, and a guarantee whose
 *       clock starts earlier than the policy's is a claim the policy does
 *       not support. This is a truthfulness correction, not a copy edit.
 *    2. THE FIRST-PERSON VOICE ("tell me … I'll") IS THE BRIEF'S, NOT THE
 *       POLICY'S ("contact us … we will"), AND NOTHING ELSE ON THIS PAGE
 *       SPEAKS IN THE FIRST PERSON SINGULAR. It is rendered as briefed
 *       rather than silently rewritten — approved copy is locked, and the
 *       voice question is Andrew's, not this agent's. FLAGGED for his gate.
 *
 * ⛔ THE POLICY PAGE ON STAGING IS STALE AND THIS IS THE LOUDEST FLAG IN THE
 *    FILE. Staging's page 10 (post_modified 2026-08-03) still carries the
 *    SUPERSEDED "Returns and Buyer's Remorse … we generally do not accept
 *    returns" section and has NO guarantee section at all. So on STAGING
 *    this badge links to copy that contradicts it. That is a CONTENT-PARITY
 *    gap, not a code defect, and it was deliberately NOT fixed here: page
 *    copy is Andrew's and syncing production content into staging is
 *    outside this brief. On PRODUCTION the badge and the policy agree.
 *
 * ⛔ NO NEW COLOUR, NO NEW TOKEN. `--bl-forest`, `--bl-text-light` and
 *    `--bl-sans` already exist in this stylesheet and are already used by
 *    the price block immediately above.
 *
 * ⛔ IT RENDERS BELOW THE BUY BUTTON, WHICH IS WHY IT CANNOT MOVE THE FOLD
 *    MEASUREMENTS 1.8.41 BOUGHT. Everything the CYCLE160 pass measured —
 *    the cold-open price block and the primary CTA — is ABOVE this node in
 *    document order, so nothing above it can be displaced. Re-measured at
 *    four viewports after the change; see the CYCLE161 QA evidence.
 *
 * ⭐ WHY IT RENDERS INSIDE THE PER-FORMAT PANEL (twice in the DOM, once
 *    visible) RATHER THAN ONCE OUTSIDE IT: the only single-render position
 *    that is still "below the CTA" sits after the subtitle, the title list,
 *    the fine print and the individual-books exit — roughly 150px away from
 *    the button it is meant to reassure. The hidden panel is `hidden`, so
 *    its copy is out of the accessibility tree and is not announced twice.
 *    This is the same pattern the price block already uses on this page.
 *
 * @return void
 */
function bhp_bundle_render_landing_guarantee() {
	?>
	<p class="bhp-landing-guarantee">
		<span class="bhp-landing-guarantee__label">30-Day Guarantee</span>
		<span class="bhp-landing-guarantee__sep" aria-hidden="true">&mdash;</span>
		<span class="bhp-landing-guarantee__text">if these books don&rsquo;t fit your reader, tell me within 30 days of delivery and I&rsquo;ll refund you in full. Keep the books.</span>
		<a class="bhp-landing-guarantee__link" href="<?php echo esc_url( bhp_bundle_guarantee_policy_url() ); ?>">Read the policy</a>
	</p>
	<?php
}

function bhp_bundle_render_landing_pricing_panel( $format ) {
	$catalog       = bhp_bundle_catalog();
	$copy          = bhp_bundle_landing_format_copy( $format );
	$unit_price    = bhp_bundle_expected_price( $format );
	$combined      = 3 * $unit_price;
	$rule          = bhp_bundle_rules( $format )[3];
	$bundle_price  = $combined - $rule['discount'];
	$action        = 'complete_' . $format . '_smart';
	?>
	<?php
	/*
	 * ---------------------------------------------------------------------
	 * F6 / CYCLE142-CX-002 (2026-08-03) — CLOSING THE 644px.
	 * ---------------------------------------------------------------------
	 * MEASURED before, at 390 x 844 with `innerWidth` asserted: the format
	 * buttons ended at y=1087 and the buy button sat at y=1824. 644px of
	 * restatement in between, on the ONE page in the whole site whose primary
	 * CTA is below the fold at BOTH viewports (1824 vs an 844 fold on mobile;
	 * 1482 vs 900 on desktop). Every other page's first CTA lands at 437-739.
	 *
	 * The gallery is NOT the wall and is NOT touched — it costs 334px and is
	 * the best-executed component on the site. What was cut is the bulk that
	 * says again what the page has already said:
	 *
	 *   - the panel TITLE restated the format the customer had literally just
	 *     chosen ("Complete Hardcover Collection" under a Hardcover button that
	 *     is already showing "Selected");
	 *   - the "All Three Books Included" label plus a three-row checklist
	 *     restated the hero's own "Three Big Adventures. One Complete
	 *     Collection." and the three-book photograph directly above it;
	 *   - the price table spent three stacked rows on two numbers.
	 *
	 * NOTHING FACTUAL IS REMOVED. All three titles, the combined price, the
	 * collection price, the shipping figure, the saving and the ages line are
	 * all still on screen — as one inline list and one two-line price block
	 * instead of eleven stacked rows. The binding/material subtitle STAYS,
	 * deliberately: it is the only thing on the page that justifies the
	 * hardcover's price, and it is the default format.
	 */
	?>
	<?php
	/*
	 * ═══════════════════════════════════════════════════════════════════
	 * ⭐ 1.8.32 — CTA FIRST, THEN THE PRICE. CYCLE147-LD-02.
	 * ═══════════════════════════════════════════════════════════════════
	 *
	 * Andrew Signore, 2026-08-07 (⛔ RELAYED, not witnessed here), gave the
	 * placement explicitly: the primary CTA goes above the fold, DIRECTLY
	 * above the "Complete Collection · price" element, and directly below
	 * the checkmark/trust line.
	 *
	 * ⛔ A MOVE, NOT A REWRITE. Same form, same nonce, same
	 *    `bhp_bundle_action`, same `complete_{format}_smart`, same
	 *    `bhp_bundle_checkout_redirect_input()` two-click machinery, same
	 *    `data-bhp-landing-main-cta` hook. `bundle-drawer.js` intercepts by
	 *    `form.bhp-bundle-form` and `bundle-landing.js` toggles by
	 *    `[data-bhp-format-panel]`; neither reads position, so neither
	 *    changes behaviour. ONLY the LABEL changed, and only to the
	 *    benefit-driven wording — see bhp_bundle_landing_primary_cta_label().
	 *
	 * ⛔ WHAT MOVED DOWN, AND WHY IT HAD TO. The material subtitle and the
	 *    three-title list are format DETAIL. Measured at 390x844 with
	 *    `innerWidth` asserted, leaving them above the button put the button
	 *    at y≈868 — below the 844 fold — which is the one thing this release
	 *    exists to prevent. They now sit with the format selector, below the
	 *    emotional case, exactly where Andrew put the selector. Nothing is
	 *    deleted: every title, the binding description, the fine print and
	 *    the individual-books exit are all still on the page, once each.
	 */
	?>
	<div class="bhp-landing-panel bhp-landing-panel--compact" data-bhp-format-panel="<?php echo esc_attr( $format ); ?>" <?php echo bhp_bundle_landing_default_format() === $format ? '' : 'hidden'; ?>>
		<h2 class="bhp-landing-panel__title screen-reader-text"><?php echo esc_html( $copy['title'] ); ?></h2>

		<form method="post" class="bhp-bundle-form bhp-landing-panel__form bhp-landing-panel__form--lead">
			<?php bhp_bundle_nonce_input(); ?>
			<input type="hidden" name="bhp_bundle_action" value="<?php echo esc_attr( $action ); ?>" />
			<?php bhp_bundle_checkout_redirect_input(); // P2-5: one tap -> /checkout/ ?>
			<button type="submit" class="button bhp-landing-cta bhp-landing-cta--primary" data-bhp-landing-main-cta>
				<?php echo esc_html( bhp_bundle_landing_primary_cta_label( $format ) ); ?>
			</button>
		</form>

		<?php
		/*
		 * 1.8.46 — the guarantee sits DIRECTLY below the buy button and
		 * nowhere else. See bhp_bundle_render_landing_guarantee() for the
		 * verbatim policy source, the two reported wording deviations and
		 * the staging content-parity flag.
		 */
		bhp_bundle_render_landing_guarantee();
		?>

		<?php
		/*
		 * ═══════════════════════════════════════════════════════════════
		 * ⛔⭐ 1.8.41 (2026-08-14, `CYCLE160-LD-COLLECTION-PRICE-BOX`) — THE
		 *     PRICE ROW AND THE SAVINGS BADGE ARE GONE FROM THIS BOX. THEY
		 *     ARE NOT DELETED FROM THE PAGE — THEY MOVED UP, INTO THE FREE
		 *     BOX, ABOVE THE FOLD, WHICH IS WHERE ANDREW ASKED FOR THEM.
		 * ═══════════════════════════════════════════════════════════════
		 *
		 * The superseded markup, preserved verbatim so the movement is
		 * visible and is not re-derived:
		 *
		 *   <dl class="bhp-landing-panel__price-rows bhp-landing-panel__price-rows--compact">
		 *     <div class="bhp-landing-panel__price-row bhp-landing-panel__price-row--main">
		 *       <dt>Complete Collection</dt>
		 *       <dd>
		 *         <span class="bhp-landing-panel__price-strike">$<?php echo esc_html( number_format( $combined, 2 ) ); ?></span>
		 *         <?php echo esc_html( '$' . number_format( $bundle_price, 2 ) ); ?>
		 *       </dd>
		 *     </div>
		 *   </dl>
		 *   …and, in the savings block below:
		 *   <span class="bhp-landing-panel__savings-badge"><?php echo esc_html( $rule['save'] ); ?></span>
		 *
		 * ⛔ WHY IT IS A DE-DUPLICATION AND NOT A RETRACTION. After 1.8.41
		 *    the SAME three numbers — the struck sum-of-singles, the
		 *    collection price and the saving — render in
		 *    `bhp_bundle_render_landing_coldopen_price()`, roughly 60px
		 *    ABOVE this point and above both the fold and the consent
		 *    banner. Leaving these rows in place would restate all three
		 *    inside the same card, which is the exact defect 1.8.39
		 *    removed from this same box ("the one a visitor reads as filler
		 *    between the price and the button").
		 *
		 * ⛔ NOTHING FACTUAL LEAVES THE PAGE. Price, strike, saving: above.
		 *    Ages line: still here. Titles, binding subtitle, fine print,
		 *    individual-books exit: all still here, once each. The
		 *    format pills below still print BOTH collection prices, and
		 *    `bhp_bundle_render_landing_value_comparison()` still prints the
		 *    full combined-vs-bundle table for both formats.
		 *
		 * ⚠ `$combined` and `$bundle_price` remain computed above and are
		 *   deliberately left in place: they are what the two test suites
		 *   compare the cold-open block's numbers against, and removing
		 *   them would make a future reader think the panel never knew the
		 *   price.
		 */
		?>
		<?php
			/*
			 * ═══════════════════════════════════════════════════════════
			 * ⛔⭐ 1.8.39 (2026-08-12, `CYCLE154-LD-COLLECTION-TRIM`) — THE
			 *     TWO CHECKMARK BENEFIT ROWS ARE REMOVED FROM THIS BOX.
			 *     "TRIM C." THIS IS A DE-DUPLICATION, NOT A RETRACTION.
			 * ═══════════════════════════════════════════════════════════
			 *
			 * Andrew Signore, 2026-08-11/12, on the reviewed options for this
			 * page: "I agree with all changes". ⛔ RELAYED through
			 * `chief-of-staff` (Gandalf); NOT witnessed first-hand here.
			 *
			 * The superseded markup, preserved verbatim so the movement is
			 * visible and is not re-derived — it is the 1.8.32 bold-bullet
			 * treatment of the 1.8.23 Shipping row and the 1.8.27 Activity
			 * Book row, both of which did exactly what they were asked to do:
			 *
			 *   <div class="bhp-landing-panel__price-row bhp-landing-panel__price-row--meta bhp-landing-panel__price-row--benefit">
			 *     <dt>Shipping</dt>
			 *     <dd><?php echo esc_html( bhp_bundle_shipping_display( $rule['shipping'], 'row' ) ); ?></dd>
			 *   </div>
			 *   <?php if ( function_exists( 'bhp_bundle_addon_free_with_collection' ) && bhp_bundle_addon_free_with_collection() ) : ?>
			 *     <div class="bhp-landing-panel__price-row bhp-landing-panel__price-row--meta bhp-landing-panel__price-row--benefit">
			 *       <dt>Activity Book</dt>
			 *       <dd><?php echo esc_html( bhp_bundle_addon_free_display() ); ?></dd>
			 *     </div>
			 *   <?php endif; ?>
			 *
			 * ⛔ WHY THEY GO, AND WHY NOTHING IS LOST. MEASURED on staging at
			 *    390x844 with `window.innerWidth` asserted, 2026-08-12: the
			 *    words FREE SHIPPING rendered THREE times inside the first
			 *    screen and a half — the cold-open bullet at y=596, the CTA
			 *    label at y=662, and this row at y=793 — and FREE ACTIVITY
			 *    BOOK twice (y=617 and y=828). The cold-open bullet list is
			 *    the canonical statement of the free items on this page: it
			 *    is FIRST, it is the shared three-item list (Shipping,
			 *    Activity Book, Vocabulary Cards), and it is gated
			 *    item-by-item on the same live predicates these rows used.
			 *    The closing CTA repeats the same three. This box was the
			 *    THIRD statement of a subset of them, and it is the one a
			 *    visitor reads as filler between the price and the button.
			 *
			 * ⛔ THE PRICE BOX KEEPS EVERY NUMBER IT EVER CARRIED: the
			 *    "Complete Collection" row with the struck-through combined
			 *    price and the collection price, and below it the savings
			 *    badge and the ages line. No price, discount, saving, tax or
			 *    total is changed, computed differently, or dropped by this
			 *    edit — it deletes two presentational rows whose values were
			 *    both the word FREE.
			 *
			 * ⛔ NOT A GATE CHANGE. `bhp_bundle_shipping_display()`,
			 *    `bhp_bundle_addon_free_with_collection()` and
			 *    `bhp_bundle_addon_free_display()` are untouched and are all
			 *    still called on this page by the cold-open and the closing
			 *    CTA. If the Activity Book stops resolving on an environment,
			 *    both surviving surfaces still fall silent by themselves.
			 *
			 * ⭐ THE `--benefit` CSS IS DELIBERATELY LEFT IN PLACE, INERT, in
			 *    `bundle-landing.css`. Nothing else uses it today, so it
			 *    renders nothing; keeping it makes a revert a one-file change
			 *    and keeps the 1.8.32 treatment recoverable verbatim.
			 */
			?>

		<div class="bhp-landing-panel__savings">
			<span class="bhp-landing-panel__ages">Ages 6&ndash;9</span>
		</div>

		<p class="bhp-landing-panel__subtitle"><?php echo esc_html( $copy['subtitle'] ); ?></p>

		<p class="bhp-landing-panel__included-inline">
			<?php
			$bhp_titles = array();
			foreach ( $catalog[ $format ] as $info ) {
				$bhp_titles[] = $info['label'];
			}
			echo esc_html( implode( ' · ', $bhp_titles ) );
			?>
		</p>

		<?php
		/*
		 * 2026-08-02: "Printed and shipped in the USA" REMOVED from this line.
		 *
		 * It was a country-of-origin claim with no located source. Searched and
		 * found nothing: repo docs/ (including bookvault-chronology.md and
		 * fulfillment-copy-correction-2026-07-09.md), the Business OS corpus and
		 * the Strategy corpus. The 2026-07-09 fulfilment-copy record states in
		 * its own "Not claimed / not added" section that no copy was added
		 * claiming "domestic-only printing" -- yet this line claimed exactly
		 * that, on the highest-value page, while none of the three product pages
		 * made any such claim. Bookvault is a print-on-demand network with
		 * facilities in more than one country; nothing in reach establishes
		 * where any given order is printed.
		 *
		 * The two surviving statements are both mechanically verifiable:
		 * checkout is served over TLS, and Bookvault supplies tracking.
		 *
		 * To restore it, a Bookvault country-of-print record must exist and be
		 * cited here. Tracked as CYCLE140-CX-10.
		 */
		?>
		<p class="bhp-landing-panel__fine-print">Secure checkout &middot; Tracking provided</p>

		<!-- Quiet tertiary exit, moved below the primary CTA and fine print
		     so it never visually competes with "Add the Complete ___
		     Collection" inside the purchase card (conversion correction). -->
		<a href="<?php echo esc_url( home_url( '/books/' ) ); ?>" class="bhp-landing-panel__view-link">View Individual Books</a>
	</div>
	<?php
}

/**
 * Section 8: trust badges. Every badge here is either a plain factual
 * statement (age range, read-aloud friendliness) or reuses the theme's
 * existing verified-review infrastructure (Kirkus, Amazon five-star
 * reviews) -- nothing invents a new statistic. "Placed in 40 Boise
 * classrooms" replaces the earlier "Used in 40 classrooms" wording
 * (Sprint A, 2026-07-16): Andrew confirmed the defensible fact is that
 * books were physically placed in 40 Boise-area classrooms -- actual
 * classroom use, reading frequency, and outcomes are not confirmed, so
 * the badge states placement only, never usage/adoption/endorsement.
 *
 * ⭐ N4 (2026-08-03) — THE COUNT IS NOW GONE. The badge reads "Placed in
 *    classrooms across Boise".
 *
 *    Andrew, production walk, verbatim: "It was placed in 40 boise
 *    classrooms - that number is going to change constantly - whats another
 *    way to say it without a number", then "number 1 for sure" of the
 *    numberless option. (Relayed through the Chief of Staff; NOT witnessed
 *    first-hand here.)
 *
 *    ⛔ THE PARAGRAPH ABOVE IS PRESERVED, NOT REWRITTEN. It is the only
 *       record of WHY the verb is "placed" rather than "used", and that
 *       reasoning is unchanged and still binding — this edit drops a number
 *       that goes stale, it does not widen the claim. Placement remains the
 *       only thing asserted; use, frequency and outcomes remain unclaimed.
 *       The claim was attested true at 40 as of 2026-08-03.
 */
function bhp_bundle_render_landing_trust_row() {
	$has_reviews = function_exists( 'bhp_get_approved_amazon_reviews_for_book' )
		&& ( bhp_get_approved_amazon_reviews_for_book( 'mariana_trench' ) || bhp_get_approved_amazon_reviews_for_book( 'mount_everest' ) );
	?>
	<div class="bhp-landing-trust-row">
		<span class="bhp-landing-trust-badge">Placed in classrooms across Boise</span>
		<span class="bhp-landing-trust-badge">Great for ages 6&ndash;9</span>
		<span class="bhp-landing-trust-badge">Read-aloud &amp; independent friendly</span>
		<?php
		/*
		 * 2026-08-02: scoped from the unqualified "Five-star reader reviews".
		 * This page sells all three titles as one product, and $has_reviews is
		 * an OR across Mariana and Everest -- so the unqualified badge asserted
		 * review proof for a bundle whose third book (The Amazon, published
		 * 2026-06-26) has none. The approved registry inc/amazon-reviews.php
		 * holds four 5-star reviews for The Mariana Trench, two for Mount
		 * Everest and ZERO for The Amazon. Stars and schema unchanged --
		 * CYCLE140-CX-9 (whether a bare glyph run reads as an aggregate) is
		 * Andrew's presentation call and is deliberately not resolved here.
		 */
		?>
		<?php if ( $has_reviews ) : ?>
			<span class="bhp-landing-trust-badge bhp-landing-trust-badge--gold">&#9733;&#9733;&#9733;&#9733;&#9733; Five-star reader reviews on our first two titles</span>
		<?php endif; ?>
		<span class="bhp-landing-trust-badge">Featuring a Kirkus-reviewed title</span>
	</div>
	<?php
}

/**
 * Section 9: testimonial. Verified Amazon review of Adventures of
 * Charlotte and Henry: The Mariana Trench, supplied directly by Andrew
 * (2026-07-05). Now registered as amz-mariana-04 in
 * bhp_get_amazon_review_registry() (inc/amazon-reviews.php) -- the source
 * URL is read from that single registry entry rather than duplicated
 * here, so there is only one place it could ever go stale.
 */
function bhp_bundle_get_testimonial_source_url() {
	if ( function_exists( 'bhp_get_amazon_review_registry' ) ) {
		foreach ( bhp_get_amazon_review_registry() as $review ) {
			if ( 'amz-mariana-04' === $review['id'] ) {
				return $review['source_url'];
			}
		}
	}
	return '';
}

/**
 * Conversion correction (2026-07-06): the "Read on Amazon" link used to
 * sit directly beneath this early-funnel testimonial, giving a third-party
 * marketplace an exit right next to the top of the page before the
 * visitor has even reached a purchase decision. The quote and "Verified
 * Amazon review" attribution stay here (they're trust content, and
 * accurate), but the actual outbound link now renders once, quietly,
 * near the final CTA instead -- see bhp_bundle_render_landing_amazon_source_link().
 */
function bhp_bundle_render_landing_testimonial() {
	?>
	<div class="bhp-landing-testimonial">
		<div class="bhp-landing-testimonial__stars" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
		<blockquote>
			<p>&ldquo;<?php echo bhp_bundle_landing_testimonial_quote(); // phpcs:ignore WordPress.Security.EscapeOutput -- approved locked copy, a literal with intentional HTML entities and no tags; escaping it would print the entities verbatim. ?>&rdquo;</p>
		</blockquote>
		<cite>Payton, elementary teacher - Verified Amazon review</cite>
	</div>
	<?php
}

/**
 * Quiet tertiary link to the source review, placed after the final CTA so
 * it never competes with the direct-purchase action. Opens in a new tab
 * with safe rel attributes; renders nothing if no verified source URL
 * exists (never fabricates one).
 */
function bhp_bundle_render_landing_amazon_source_link() {
	$source_url = bhp_bundle_get_testimonial_source_url();
	if ( ! $source_url ) {
		return;
	}
	?>
	<p class="bhp-landing-amazon-source">
		<a
			href="<?php echo esc_url( $source_url ); ?>"
			rel="noopener noreferrer"
			target="_blank"
			<?php
			/*
			 * ⭐ CYCLE144-LD-221 (2026-08-05) — WCAG 2.5.3 (Label in Name).
			 *
			 * MEASURED, Lighthouse 12.8.2 / axe, staging /complete-collection/,
			 * mobile: `label-content-name-mismatch` FAILED on this anchor —
			 * "Text inside the element is not included in the accessible name".
			 * The visible label is "Read the verified review on Amazon"; the old
			 * accessible name began "Read the verified Amazon review of…", which
			 * shares words but contains the visible phrase nowhere.
			 *
			 * A speech-input user saying the words they can SEE could not
			 * activate this link. The name now leads with the visible label
			 * verbatim and keeps the disambiguating context after it.
			 *
			 * ⛔ NOTHING VISIBLE CHANGES, and no review, rating or quote is
			 *    altered, added or invented — this is the link's label only.
			 */
			?>
			aria-label="<?php esc_attr_e( 'Read the verified review on Amazon — The Mariana Trench, the review quoted above (opens in a new tab)', 'brave-hearts' ); ?>"
			data-bhp-event="customer_review_source_click"
			data-bhp-book="mariana_trench"
			data-bhp-source="complete_collection_testimonial"
		><?php esc_html_e( 'Read the verified review on Amazon', 'brave-hearts' ); ?></a>
	</p>
	<?php
}

/**
 * Sections 10-11: three-book story section doubles as the learning-
 * benefit cards -- each card's bullet list IS the "what children learn"
 * content for that book, so one section satisfies both requirements
 * without repeating the same three books twice on the page.
 */
function bhp_bundle_render_landing_story_section() {
	$books = array(
		array(
			'eyebrow' => 'Book One',
			'title'   => 'The Mariana Trench',
			'points'  => array( 'Deep-sea science', 'Ocean exploration', 'Courage in the unknown' ),
		),
		array(
			'eyebrow' => 'Book Two',
			'title'   => 'Mount Everest',
			'points'  => array( 'Mountain science', 'Historic explorers', 'Teamwork &amp; perseverance' ),
		),
		array(
			'eyebrow' => 'Book Three',
			'title'   => 'The Amazon',
			'points'  => array( 'Rainforest wildlife', 'River systems', 'Conservation &amp; kindness' ),
		),
	);
	?>
	<section class="bhp-landing-story">
		<header class="bhp-landing-story__header">
			<p class="bhp-landing-eyebrow bhp-landing-eyebrow--center">What Children Discover</p>
			<h2>Three remarkable places. Three unforgettable journeys.</h2>
		</header>
		<div class="bhp-landing-story__grid">
			<?php foreach ( $books as $book ) : ?>
				<div class="bhp-landing-story__card">
					<span class="bhp-landing-story__card-eyebrow"><?php echo esc_html( strtoupper( $book['eyebrow'] ) ); ?></span>
					<h3><?php echo esc_html( $book['title'] ); ?></h3>
					<ul>
						<?php foreach ( $book['points'] as $point ) : ?>
							<li><?php echo wp_kses_post( $point ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

/**
 * Section 11b: the expanded Kirkus block (P4a, 2026-08-02).
 *
 * Closes gap G4 from the 2026-08-02 trust-signal audit: this page carried the
 * Kirkus *badge* in the trust row with no quote and no link, while the homepage
 * carried the full quote -- the strongest third-party asset was weakest on the
 * highest-value page.
 *
 * ZERO new copy. This calls the theme's existing centralised component with the
 * existing approved quote; the quote, attribution, reviewed title and review URL
 * all come from bhp_get_kirkus_review_data() and are never duplicated here. The
 * component emits plain blockquote/cite semantics and no Review or
 * AggregateRating microdata.
 *
 * Placed after the three-book story section so the reader has just seen that
 * only one of the three books is named as the reviewed title, and before the
 * value comparison. Renders nothing at all if the theme component is absent
 * (plugin can outlive a theme swap) or if the approved data is incomplete --
 * fail-closed, never an empty frame.
 */
function bhp_bundle_render_landing_kirkus() {
	if ( ! function_exists( 'bhp_render_kirkus_credibility' ) ) {
		return;
	}
	$markup = bhp_render_kirkus_credibility(
		'expanded',
		array(
			'source'    => 'complete_collection_landing',
			'show_link' => true,
		)
	);
	if ( ! trim( (string) $markup ) ) {
		return;
	}
	?>
	<section class="bhp-landing-kirkus" aria-label="Kirkus Reviews">
		<div class="bhp-landing-kirkus__inner">
			<?php echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput -- component output is already escaped at source. ?>
		</div>
	</section>
	<?php
}

/**
 * Section 12: value comparison. Figures pulled from the same
 * bundle-data.php functions as everything else on the page.
 */
function bhp_bundle_render_landing_value_comparison() {
	$pb_combined = 3 * bhp_bundle_expected_price( 'paperback' );
	$hc_combined = 3 * bhp_bundle_expected_price( 'hardcover' );
	$pb_bundle   = $pb_combined - bhp_bundle_rules( 'paperback' )[3]['discount'];
	$hc_bundle   = $hc_combined - bhp_bundle_rules( 'hardcover' )[3]['discount'];
	?>
	<section class="bhp-landing-value">
		<div class="bhp-landing-value__inner">
			<p class="bhp-landing-eyebrow bhp-landing-eyebrow--on-dark bhp-landing-eyebrow--center">The Complete Collection Value</p>
			<h2 class="bhp-landing-value__heading">Complete the adventure and save.</h2>
			<div class="bhp-landing-value__panels">
				<div class="bhp-landing-value__panel bhp-landing-value__panel--dark">
					<p class="bhp-landing-value__panel-label">Buy Individually</p>
					<div class="bhp-landing-value__row"><span>Paperback</span><span>$<?php echo esc_html( number_format( $pb_combined, 2 ) ); ?></span></div>
					<div class="bhp-landing-value__row"><span>Hardcover</span><span>$<?php echo esc_html( number_format( $hc_combined, 2 ) ); ?></span></div>
				</div>
				<div class="bhp-landing-value__panel bhp-landing-value__panel--light">
					<p class="bhp-landing-value__panel-label">Complete Collection <span class="bhp-landing-value__best-badge">Best Value</span></p>
					<div class="bhp-landing-value__row"><span>Paperback</span><span>$<?php echo esc_html( number_format( $pb_bundle, 2 ) ); ?></span></div>
					<div class="bhp-landing-value__row"><span>Hardcover</span><span>$<?php echo esc_html( number_format( $hc_bundle, 2 ) ); ?></span></div>
				</div>
			</div>
			<p class="bhp-landing-value__footnote">
				Paperback &middot; Save <?php echo esc_html( '$' . number_format( bhp_bundle_rules( 'paperback' )[3]['discount'], 2 ) ); ?>
				&nbsp;&nbsp;Hardcover &middot; Save <?php echo esc_html( '$' . number_format( bhp_bundle_rules( 'hardcover' )[3]['discount'], 2 ) ); ?>
			</p>
			<ul class="bhp-landing-value__non-price">
				<li>All three adventures in one purchase</li>
				<li>One complete home or classroom collection</li>
				<li>Paperback or hardcover - your choice</li>
				<li>One shipment</li>
				<li>Three full reading experiences</li>
				<li>Direct from the publisher</li>
			</ul>
		</div>
	</section>
	<?php
}

/**
 * Section 13: gift-focused section.
 */
function bhp_bundle_render_landing_gift_section() {
	?>
	<section class="bhp-landing-gift">
		<div class="bhp-landing-gift__text">
			<p class="bhp-landing-eyebrow">The Perfect Present</p>
			<h2>A Gift That Opens a World of Adventure</h2>
			<p>A complete adventure collection that grows with young readers - from first read-alouds to confident independent chapters.</p>
			<a href="#bhp-landing-pricing-card" class="button bhp-landing-cta bhp-landing-cta--gold" data-bhp-scroll-to-card>Get the Complete Collection</a>
		</div>
		<div class="bhp-landing-gift__tags">
			<?php foreach ( array( 'Birthdays', 'Holidays', 'Classrooms', 'Homeschool libraries', 'Young explorers', 'Reluctant readers' ) as $tag ) : ?>
				<span class="bhp-landing-gift__tag"><?php echo esc_html( $tag ); ?></span>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

/**
 * F5 / CYCLE142-CX-003 (2026-08-03) — the sticky buy bar the flagship page
 * was the only page without.
 *
 * MEASURED before: through 6,809px of scroll on /complete-collection/ the only
 * sticky element was the 93px site header. All FIVE audience landing pages
 * already carry `audience-landing-stickybar` — fixed, bottom-anchored, proven
 * and shipped. The $48.99 page, the one the majority of phone buyers are being
 * sent to, was the one page with no persistent way to buy.
 *
 * THIS REUSES THE SHIPPED COMPONENT'S BEHAVIOUR, not a new invention: appear
 * past a scroll threshold, suppress itself while the real CTA is already on
 * screen (so it never stacks a duplicate button on top of an identical one),
 * respect the safe-area inset, and stay out of the floating cart's way. The
 * markup and CSS live in the plugin rather than the theme only because this
 * page is rendered by the plugin's shortcode.
 *
 * IT IS A REAL BUY BUTTON, NOT AN ANCHOR. It submits the same
 * `form.bhp-bundle-form` that `bundle-drawer.js` already intercepts, with the
 * same `complete_{format}_smart` action, so a tap opens the same drawer with
 * the same smart de-duplication. `bundle-landing.js` keeps its action and its
 * label in step with the format selector, so the bar can never offer a format
 * the panel above is not showing.
 *
 * The label carries the live price so the bar is an offer, not a nag.
 */
function bhp_bundle_render_landing_sticky_bar() {
	$default = bhp_bundle_landing_default_format();
	$rule    = bhp_bundle_rules( $default )[3];
	$price   = ( 3 * bhp_bundle_expected_price( $default ) ) - $rule['discount'];
	?>
	<div class="bhp-landing-stickybar" data-bhp-landing-stickybar>
		<div class="bhp-landing-stickybar__row">
			<span class="bhp-landing-stickybar__text" data-bhp-stickybar-text><?php echo esc_html( 'Complete Collection · $' . number_format( $price, 2 ) ); ?></span>
			<form method="post" class="bhp-bundle-form bhp-landing-stickybar__form">
				<?php bhp_bundle_nonce_input(); ?>
				<input type="hidden" name="bhp_bundle_action" value="<?php echo esc_attr( 'complete_' . $default . '_smart' ); ?>" data-bhp-stickybar-action />
				<?php bhp_bundle_checkout_redirect_input(); // P2-5: one tap -> /checkout/ ?>
				<button type="submit" class="button bhp-landing-cta bhp-landing-cta--gold bhp-landing-stickybar__cta" data-bhp-landing-sticky-cta><?php esc_html_e( 'Add to cart', 'brave-hearts' ); ?></button>
			</form>
		</div>
	</div>
	<?php
}

/**
 * Section 14: final closing CTA. Mirrors the hero panel's per-format
 * copy/pricing via the same data-bhp-format-panel toggle mechanism so it
 * always agrees with the main pricing card above.
 */
function bhp_bundle_render_landing_final_cta() {
	?>
	<section class="bhp-landing-final">
		<div class="bhp-landing-final__inner">
			<p class="bhp-landing-eyebrow bhp-landing-eyebrow--on-dark bhp-landing-eyebrow--center">The Complete Collection</p>
			<h2>Ready to Explore the World?</h2>
			<p>Choose paperback or hardcover and bring home the complete Adventures of Charlotte and Henry collection.</p>

			<?php /* C1 — same ordering call, same reasoning as the pricing panels. */ ?>
			<?php foreach ( bhp_bundle_landing_format_order() as $format ) :
				$copy   = bhp_bundle_landing_format_copy( $format );
				$rule   = bhp_bundle_rules( $format )[3];
				$price  = ( 3 * bhp_bundle_expected_price( $format ) ) - $rule['discount'];
				$action = 'complete_' . $format . '_smart';
			?>
				<div class="bhp-landing-final__panel" data-bhp-format-panel="<?php echo esc_attr( $format ); ?>" <?php echo bhp_bundle_landing_default_format() === $format ? '' : 'hidden'; ?>>
					<form method="post" class="bhp-bundle-form">
						<?php bhp_bundle_nonce_input(); ?>
						<input type="hidden" name="bhp_bundle_action" value="<?php echo esc_attr( $action ); ?>" />
						<?php bhp_bundle_checkout_redirect_input(); // P2-5: one tap -> /checkout/ ?>
						<button type="submit" class="button bhp-landing-cta bhp-landing-cta--gold" data-bhp-landing-lower-cta>
							<?php echo esc_html( $copy['cta'] ); ?>
						</button>
					</form>
					<?php
					/*
					 * ═══════════════════════════════════════════════════════
					 * ⭐ 1.8.37 (2026-08-09, `CYCLE148-LD-09`) — THE LOWER
					 *    FINE-PRINT STOPS RUNNING THE FREE ITEMS TOGETHER.
					 * ═══════════════════════════════════════════════════════
					 *
					 * The superseded markup, preserved so the movement is
					 * visible rather than re-derived:
					 *
					 *   <p class="bhp-landing-final__fine-print">
					 *     Paperback · $31.99 · FREE shipping ·
					 *     FREE Activity Book · Save $3.98
					 *   </p>
					 *
					 * ⛔ THE DEFECT. Andrew Signore's 2026-08-06 ruling
					 *    (RELAYED, not witnessed first-hand) is "bold, each
					 *    free item its own bullet line, NEVER COMBINED
					 *    SENTENCES." 1.8.36 fixed the COLD-OPEN at the top of
					 *    this page and left this one, at the bottom, still
					 *    chaining both free items into a middot run with the
					 *    format, the price and the savings — five claims in one
					 *    grey 0.78rem line, where the two that matter most are
					 *    the two nobody reads. This is the last surface on the
					 *    page before the visitor decides.
					 *
					 * ⭐ THE SHAPE IS THE COLD-OPEN'S, deliberately: same
					 *    gated-array construction, same `<li><strong>` bullet,
					 *    same all-caps-in-the-string rule, same helpers. The
					 *    two ends of the page now make the same promise in the
					 *    same words, which is the whole point of routing both
					 *    through `bhp_bundle_addon_free_offer_line()`.
					 *
					 * ⛔ GATED, NOT TYPED. Shipping earns a bullet only when
					 *    THIS format's three-book rule actually resolves free
					 *    (`bhp_bundle_shipping_is_free()`); the Activity Book
					 *    earns one only when the offer is live on THIS
					 *    environment. When shipping is NOT free it keeps its
					 *    place in the fine-print line below with its real
					 *    amount — the claim moves, it never silently vanishes,
					 *    and no line ever promises something the cart will
					 *    charge for.
					 *
					 * ⛔ THE FINE PRINT KEEPS EXACTLY WHAT IS ACTUALLY FINE
					 *    PRINT: format, price, savings. Nothing was deleted;
					 *    two claims were promoted out of it.
					 */
					$bhp_final_free = array();
					if ( bhp_bundle_shipping_is_free( $rule['shipping'] ) ) {
						// ⭐ 1.8.52 — the same school-visit swap as the cold-open
						//    at the top of this page. Both ends of the page make
						//    the same promise in the same words, which is the
						//    whole reason both route through one helper.
						$bhp_final_free[] = bhp_school_visit_use_delivery_framing()
							? bhp_school_visit_delivery_bullet()
							: 'FREE Shipping on the complete collection';
					}
					if ( function_exists( 'bhp_bundle_addon_free_with_collection' )
						&& bhp_bundle_addon_free_with_collection()
						&& function_exists( 'bhp_bundle_addon_free_offer_line' ) ) {
						$bhp_final_free[] = bhp_bundle_addon_free_offer_line();
					}
					/*
					 * ⭐ 1.8.38 - THE THIRD BULLET, same rule and same order
					 *    as the cold-open at the top of this page: Shipping,
					 *    Activity Book, Vocabulary Cards. Both ends of the
					 *    page make the same three promises in the same words,
					 *    which is the whole reason both route through the
					 *    plugin's own offer-line helpers.
					 */
					if ( function_exists( 'bhp_bundle_vocab_cards_live' )
						&& bhp_bundle_vocab_cards_live() ) {
						$bhp_final_free[] = bhp_bundle_vocab_free_offer_line();
					}
					?>
					<?php if ( $bhp_final_free ) : ?>
						<ul class="bhp-landing-final__free">
							<?php foreach ( $bhp_final_free as $bhp_final_line ) : ?>
								<li><strong><?php echo esc_html( $bhp_final_line ); ?></strong></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<p class="bhp-landing-final__fine-print">
						<?php echo esc_html( $copy['material_tag'] ); ?> &middot;
						$<?php echo esc_html( number_format( $price, 2 ) ); ?> &middot;
						<?php
						// 1.8.23: same figure, centralised wording. 1.8.37: it
						// prints here ONLY when it is not already a bullet
						// above, so the page never states shipping twice.
						?>
						<?php if ( ! bhp_bundle_shipping_is_free( $rule['shipping'] ) ) : ?>
							<?php echo esc_html( bhp_bundle_shipping_display( $rule['shipping'] ) ); ?> &middot;
						<?php endif; ?>
						<?php echo esc_html( $rule['save'] ); ?>
					</p>
				</div>
			<?php endforeach; ?>
			<?php bhp_bundle_render_landing_amazon_source_link(); ?>
		</div>
	</section>
	<?php
}
