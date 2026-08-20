<?php
/**
 * "The book this came from" — the in-body bridge from a field note to a book.
 *
 * Rendered by `bhp_blog_rail_html()`; every fact on it is resolved and
 * validated there. This file draws, it does not decide. See
 * `inc/blog-post-template.php` §3 for the provenance rule and the CTA rule.
 *
 * ⛔ NO FIGURE ON THIS RAIL IS TYPED. Price, age band, title and cover all
 *    arrive in `$facts` from live product records, and the caller returns ''
 *    rather than render when any of them is missing.
 *
 * @package brave-hearts
 */

defined( 'ABSPATH' ) || exit;

$facts = $args['facts'] ?? array();
if ( empty( $facts['url'] ) || empty( $facts['price'] ) || empty( $facts['title'] ) ) {
	return;
}
$eyebrow = (string) ( $args['eyebrow'] ?? '' );
$cta     = $args['cta'] ?? array( 'label' => '', 'url' => $facts['url'] );
$rail_id = wp_unique_id( 'bhp-book-rail-' );

/*
 * ⭐ THE RAIL CONTRACT, MADE VISIBLE TO A TEST. See `inc/blog-post-template.php`
 *    §3a. The mode's image kind and price source are printed onto the element so
 *    the pairing can be asserted from rendered HTML — by the suite, by the
 *    deploy gate, and by anyone reading the DOM on a live page. They are
 *    declared by the resolver, never inferred here: this file draws, it does not
 *    decide. A missing declaration prints empty and FAILS the gate, which is the
 *    correct direction — a rail that cannot state its mode is not a rail that
 *    has been checked.
 */
$image_kind   = (string) ( $facts['image_kind'] ?? '' );
$price_source = (string) ( $facts['price_source'] ?? '' );
?>
<aside class="bhp-book-rail bhp-book-rail--<?php echo esc_attr( $facts['kind'] ?? 'book' ); ?>"
       aria-labelledby="<?php echo esc_attr( $rail_id ); ?>"
       data-bhp-rail-kind="<?php echo esc_attr( $facts['kind'] ?? 'book' ); ?>"
       data-bhp-rail-image="<?php echo esc_attr( $image_kind ); ?>"
       data-bhp-rail-price-source="<?php echo esc_attr( $price_source ); ?>">

	<?php if ( ! empty( $facts['image_id'] ) ) : ?>
		<a class="bhp-book-rail__cover" href="<?php echo esc_url( $facts['url'] ); ?>" tabindex="-1" aria-hidden="true">
			<?php
			/*
			 * The REAL published cover file. Standing rule §9: covers are
			 * composited from the approved artwork, never regenerated and never
			 * altered. `alt` is empty and the link is removed from the tab order
			 * because the title beside it is the same link with a real name --
			 * a screen reader should hear the book once, not twice.
			 */
			echo wp_get_attachment_image(
				(int) $facts['image_id'],
				'medium',
				false,
				array(
					// The modifier names WHICH KIND of image this is -- a single
					// title's cover, or the three-book collection composite. It
					// is the class the contract assertion reads.
					'class'   => 'bhp-book-rail__img bhp-book-rail__img--' . sanitize_html_class( $image_kind ),
					'alt'     => '',
					'loading' => 'lazy',
					'decoding' => 'async',
				)
			);
			?>
		</a>
	<?php endif; ?>

	<div class="bhp-book-rail__body">
		<p class="bhp-book-rail__eyebrow" id="<?php echo esc_attr( $rail_id ); ?>"><?php echo esc_html( $eyebrow ); ?></p>
		<p class="bhp-book-rail__title"><a href="<?php echo esc_url( $facts['url'] ); ?>"><?php echo esc_html( $facts['title'] ); ?></a></p>
		<p class="bhp-book-rail__facts">
			<span class="bhp-book-rail__ages"><?php echo esc_html( $facts['age_range'] ); ?></span>
			<span class="bhp-book-rail__sep" aria-hidden="true"> · </span>
			<span class="bhp-book-rail__price"><?php echo esc_html( $facts['price'] ); ?></span>
		</p>
	</div>

	<a class="bhp-book-rail__cta btn btn-cta-primary"
	   href="<?php echo esc_url( $cta['url'] ); ?>"
	   data-bhp-event="blog_book_rail_click"
	   data-bhp-source="blog-post-rail"
	   data-bhp-rail-mode="<?php echo esc_attr( $cta['mode'] ?? '' ); ?>"><?php echo esc_html( $cta['label'] ); ?></a>
</aside>
