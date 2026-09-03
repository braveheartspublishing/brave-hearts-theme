<?php
/**
 * Brave Hearts Bundle Pricing — Shop the Series page.
 *
 * Renders [bhp_shop_the_series]: two format sections (Paperback,
 * Hardcover), each presenting the three approved customer paths —
 * one book, any two, complete set — with the exact approved copy and
 * fixed-dollar savings. Reuses the same add-to-cart form handler and
 * render helpers already used by [bhp_bundle_offers] (bundle-shortcode.php)
 * so there is exactly one code path for "add these titles to the cart."
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'bhp_shop_the_series', 'bhp_bundle_render_shop_series' );

function bhp_bundle_render_shop_series() {
	ob_start();
	wc_print_notices();
	?>
	<div class="bhp-shop-series">
		<?php
		bhp_bundle_render_shop_series_format_section( 'paperback', 'Paperback' );
		/*
		 * ⭐ 1.8.57 (2026-08-18, CYCLE164-LD-PAPERBACK-DEFAULT): the HARDCOVER
		 *    section is withheld from a school-visit session. Andrew cannot
		 *    carry a hardcover to a read aloud and sign it, and the bundle
		 *    plugin's `includes/school-visit-paperback-only.php` refuses every
		 *    hardcover add and checkout on such a session, so rendering three
		 *    hardcover purchase forms here would offer what the server refuses.
		 *
		 * ⛔ CONTROL PATH: false for every ordinary shopper; the section renders
		 *    exactly as in 1.8.56.
		 */
		if ( ! function_exists( 'bhp_school_visit_paperback_only' ) || ! bhp_school_visit_paperback_only() ) {
			bhp_bundle_render_shop_series_format_section( 'hardcover', 'Hardcover' );
		}
		?>
	</div>
	<?php
	return ob_get_clean();
}

function bhp_bundle_render_shop_series_format_section( $format, $format_label ) {
	$rules = bhp_bundle_rules( $format );
	$price = bhp_bundle_expected_price( $format );
	?>
	<section class="bhp-shop-series__format">
		<h2 class="bhp-shop-series__format-heading"><?php echo esc_html( $format_label ); ?></h2>
		<div class="bhp-shop-series__paths">

			<div class="bhp-shop-series__path bhp-shop-series__path--one">
				<h3>Start with One Adventure</h3>
				<p class="bhp-shop-series__price">$<?php echo esc_html( number_format( $price, 2 ) ); ?></p>
				<?php bhp_bundle_render_single_section( $format ); ?>
			</div>

			<div class="bhp-shop-series__path bhp-shop-series__path--two">
				<h3>Choose Any Two</h3>
				<p class="bhp-shop-series__price">
					$<?php echo esc_html( number_format( $rules[2]['discount'] > 0 ? ( 2 * $price - $rules[2]['discount'] ) : ( 2 * $price ), 2 ) ); ?>
					<?php
					/*
					 * ⭐ 1.8.81 (`CYCLE179-LD-356` item 4, closing
					 *    `CYCLE179-LD-16`) — RENDER-TIME, NOT BUILD-TIME.
					 *    This printed `$rules[2]['save']`, the literal in
					 *    `bhp_bundle_rules()`. 1.8.80 already made the two
					 *    shortcode boxes compute the same claim from the LIVE
					 *    product prices and go silent when a price no longer
					 *    matches the one the cart applies the discount under;
					 *    these three surfaces were left on the literal and
					 *    could therefore promise a saving the checkout would
					 *    decline.
					 * ⛔ THE AMOUNT IS NOT CHANGED BY THIS EDIT. Founder
					 *    ruling seal 820 settles that the two-paperback saving
					 *    IS $1.99, and `bhp_bundle_saving_label()` returns
					 *    exactly that from today's live prices. Only the SOURCE
					 *    of the number moves.
					 * ⛔ IT FAILS CLOSED, AND THE SEPARATOR GOES WITH IT. An
					 *    empty label must not leave a dangling " - " next to
					 *    the price.
					 */
					$bhp_series_save_2 = function_exists( 'bhp_bundle_saving_label' )
						? bhp_bundle_saving_label( $format, 2 )
						: '';
					if ( '' !== $bhp_series_save_2 ) :
						?>
 - <?php echo esc_html( $bhp_series_save_2 ); ?>
						<?php
					endif;
					?>
				</p>
				<?php
				bhp_bundle_render_any2_section(
					$format,
					'any2_' . $format,
					'hardcover' === $format ? 'Add My 2-Book Hardcover Set' : 'Add My 2-Book Paperback Set'
				);
				?>
			</div>

			<div class="bhp-shop-series__path bhp-shop-series__path--complete">
				<h3>Get the Complete Collection</h3>
				<p class="bhp-shop-series__price">
					$<?php echo esc_html( number_format( ( 3 * $price ) - $rules[3]['discount'], 2 ) ); ?>
					<?php
					// 1.8.81 - the tier-3 half of the same correction. See the note above.
					$bhp_series_save_3 = function_exists( 'bhp_bundle_saving_label' )
						? bhp_bundle_saving_label( $format, 3 )
						: '';
					if ( '' !== $bhp_series_save_3 ) :
						?>
 - <?php echo esc_html( $bhp_series_save_3 ); ?>
						<?php
					endif;
					?>
				</p>
				<?php
				bhp_bundle_render_complete_section(
					$format,
					'complete_' . $format,
					'hardcover' === $format ? 'Add the Complete Hardcover Collection' : 'Add the Complete Paperback Set'
				);
				?>
			</div>

		</div>
	</section>
	<?php
}

/**
 * "Start with One Adventure" tier: exactly one title, radio buttons
 * (not checkboxes) since only a single selection is valid.
 */
function bhp_bundle_render_single_section( $format ) {
	$catalog = bhp_bundle_catalog();
	$action  = 'single_' . $format;

	/*
	 * ⭐ 1.8.71 (2026-08-24, CYCLE166-LD-VISIT-STOCK-GATE): a chapter title
	 *    Andrew has run out of is shown DISABLED and labelled sold out, on a
	 *    visit-flagged session only.
	 *
	 * ⛔ CONTROL PATH: `$closed` is empty for every ordinary shopper and on
	 *    every environment with no shelf baseline set, and this renderer is
	 *    then byte-identical to 1.8.70.
	 * ⛔ THE ROW IS NOT REMOVED. A parent who came for one specific adventure
	 *    must be able to see that it was that adventure that sold out.
	 */
	$closed = function_exists( 'bhp_visit_shelf_closed_map_for_request' )
		? bhp_visit_shelf_closed_map_for_request()
		: array();
	?>
	<div class="bhp-bundle-card bhp-bundle-single">
		<form method="post" class="bhp-bundle-form">
			<?php
			/*
			 * 1.8.77 (`F1`): third argument `false` = emit NO `_wp_http_referer`
			 * field. On a page-cached site that field publishes one visitor's
			 * full query string (fbclid, utm_*) to the next visitor. See the
			 * long note on `bhp_bundle_nonce_input()` in bundle-landing-page.php
			 * for the live observation and for why nonce verification is
			 * unaffected.
			 */
			wp_nonce_field( 'bhp_bundle_add', 'bhp_bundle_nonce', false );
			?>
			<input type="hidden" name="bhp_bundle_action" value="<?php echo esc_attr( $action ); ?>" />
			<p class="bhp-bundle-instructions">Choose one title:</p>
			<ul class="bhp-bundle-title-list">
				<?php foreach ( $catalog[ $format ] as $title_key => $info ) : ?>
					<?php $is_closed = isset( $closed[ $title_key ] ); ?>
					<li<?php echo $is_closed ? ' class="bhp-bundle-title--sold-out"' : ''; ?>>
						<label>
							<input type="radio" name="bhp_single_title" value="<?php echo esc_attr( $title_key ); ?>"<?php echo $is_closed ? ' disabled="disabled"' : ''; ?> />
							<?php echo esc_html( $info['label'] ); ?>
							<?php if ( $is_closed ) : ?>
								<span class="bhp-bundle-sold-out-label"><?php echo esc_html( bhp_visit_shelf_sold_out_label() ); ?></span>
							<?php elseif ( function_exists( 'bhp_visit_shelf_render_counter' ) ) : ?>
								<?php
								/*
								 * ⭐ 1.8.72 — the live count, ONLY in the 2..10 window and
								 *    ONLY on a visit-flagged session. `$is_closed` has
								 *    already been ruled out by the branch above, so the two
								 *    states can never print together.
								 * ⛔ PRINTS NOTHING AT ALL otherwise. No element, no class.
								 */
								bhp_visit_shelf_render_counter( $title_key );
								?>
							<?php endif; ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
			<button type="submit" class="button bhp-bundle-submit">Add to Cart</button>
		</form>
	</div>
	<?php
}
