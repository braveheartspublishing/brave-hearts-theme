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
		bhp_bundle_render_shop_series_format_section( 'hardcover', 'Hardcover' );
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
 - <?php echo esc_html( $rules[2]['save'] ); ?>
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
 - <?php echo esc_html( $rules[3]['save'] ); ?>
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
	?>
	<div class="bhp-bundle-card bhp-bundle-single">
		<form method="post" class="bhp-bundle-form">
			<?php wp_nonce_field( 'bhp_bundle_add', 'bhp_bundle_nonce' ); ?>
			<input type="hidden" name="bhp_bundle_action" value="<?php echo esc_attr( $action ); ?>" />
			<p class="bhp-bundle-instructions">Choose one title:</p>
			<ul class="bhp-bundle-title-list">
				<?php foreach ( $catalog[ $format ] as $title_key => $info ) : ?>
					<li>
						<label>
							<input type="radio" name="bhp_single_title" value="<?php echo esc_attr( $title_key ); ?>" />
							<?php echo esc_html( $info['label'] ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
			<button type="submit" class="button bhp-bundle-submit">Add to Cart</button>
		</form>
	</div>
	<?php
}
