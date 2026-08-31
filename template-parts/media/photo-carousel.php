<?php
/**
 * Brave Hearts — reusable photograph carousel.
 *
 * Expects:
 *   $photos   array   rows of { file, alt, w, h, caption } — REQUIRED
 *   $pc_label string  the accessible name of the rail — REQUIRED
 *   $pc_id    string  a unique id stem — REQUIRED
 *
 * ---------------------------------------------------------------------------
 * ⭐ THE RAIL IS A SCROLLER. THE CONTROLS ARE THE ONLY PART THAT NEEDS JS.
 * ---------------------------------------------------------------------------
 *
 * ⛔ NO SLIDE IS `hidden`, AND THAT IS THE WHOLE ACCESSIBILITY ARGUMENT. The
 *    theme's other gallery (`template-parts/commerce/look-inside.php`) prints
 *    every slide after the first with the `hidden` attribute and relies on
 *    `book-media.js` to reveal them — so a visitor whose JavaScript failed sees
 *    ONE item and has no way to reach the rest. Here every photograph is in the
 *    DOM, visible, and reachable by swipe, trackpad, keyboard or the rail's own
 *    scrollbar with the script absent entirely.
 *
 * ⛔ THE ARROWS AND THE DOT STRIP ARE PRINTED `hidden` AND ARE UNHIDDEN BY THE
 *    SCRIPT. The inverse of the rule above, for the same reason: a control that
 *    cannot do anything must not be on the page. `assets/js/photo-carousel.js`
 *    reveals them as its first act.
 *
 * ⛔ LAZY, EXCEPT THE FIRST. Slide 1 is `loading="eager"`; every other slide is
 *    `loading="lazy"` and is genuinely off-screen inside the scroller, so a
 *    six-photograph carousel fetches ONE photograph on load. `decoding="async"`
 *    and explicit `width`/`height` on every image, so no slide can shift the
 *    page while it arrives.
 *
 * ⛔ NO `fetchpriority="high"`. This carousel is BELOW THE FOLD; raising its
 *    first image's priority would make it compete with the page's real LCP
 *    element, which is the hero. `look-inside.php` documents the same trap for
 *    its own `$eager_first`.
 *
 * ⛔ NO ATTACHMENT IDs ANYWHERE IN THIS FILE. Every `src` is a theme-asset URL
 *    built by `bhp_author_visits_photo_url()`, so the same markup renders
 *    identically on staging and production. This is 1.19.329 §7's constraint
 *    and it is why `look-inside.php` could not simply be called.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $photos ) || ! is_array( $photos ) ) {
	return; // The gate. An empty carousel is not rendered as an empty frame.
}

$pc_photos = array_values( $photos );
$pc_total  = count( $pc_photos );
$pc_label  = isset( $pc_label ) ? (string) $pc_label : __( 'Photographs', 'brave-hearts' );
$pc_id     = isset( $pc_id ) ? sanitize_html_class( (string) $pc_id ) : 'bhp-photo-carousel';

$pc_classes = array( 'bhp-photo-carousel' );
if ( $pc_total < 2 ) {
	/* One photograph is a photograph. No arrows, no dots, no counter, and the
	   rail still exists so the CSS below needs no second code path. */
	$pc_classes[] = 'bhp-photo-carousel--single';
}
?>
<div class="<?php echo esc_attr( implode( ' ', $pc_classes ) ); ?>"
     id="<?php echo esc_attr( $pc_id ); ?>"
     data-bhp-photo-carousel
     data-bhp-pc-count="<?php echo esc_attr( (string) $pc_total ); ?>">

  <div class="bhp-photo-carousel__frame">
    <?php
    /*
     * `tabindex="0"` is REQUIRED, not decorative: a scroll container is not
     * focusable by default in Firefox, and an unfocusable scroller cannot be
     * driven by the keyboard at all. With it, the arrow keys scroll the rail
     * natively before the script loads, and the script only upgrades that to
     * whole-slide steps.
     *
     * `aria-roledescription="carousel"` names the pattern without claiming the
     * full APG tabs/carousel widget contract — there is no auto-rotation, no
     * tablist and no live-region-per-slide here, and claiming a role this
     * markup does not implement is worse than not claiming one.
     */
    ?>
    <ul class="bhp-photo-carousel__rail"
        data-bhp-pc-rail
        tabindex="0"
        role="list"
        aria-roledescription="carousel"
        aria-label="<?php echo esc_attr( $pc_label ); ?>">
      <?php foreach ( $pc_photos as $pc_i => $pc_photo ) : ?>
        <?php
        $pc_url = function_exists( 'bhp_author_visits_photo_url' ) ? bhp_author_visits_photo_url( $pc_photo['file'] ) : '';
        if ( '' === $pc_url || '' === $pc_photo['alt'] ) {
            continue; // Same gate as the shipped gallery: undescribed photographs do not render.
        }
        $pc_first = ( 0 === $pc_i );
        ?>
        <li class="bhp-photo-carousel__slide"
            data-bhp-pc-slide="<?php echo esc_attr( (string) $pc_i ); ?>"
            aria-roledescription="slide"
            aria-label="<?php
            /* translators: %1$d: photograph number. %2$d: total photographs. */
            echo esc_attr( sprintf( __( 'Photograph %1$d of %2$d', 'brave-hearts' ), (int) $pc_i + 1, (int) $pc_total ) );
            ?>">
          <figure class="bhp-photo-carousel__figure">
            <img
              class="bhp-photo-carousel__img"
              src="<?php echo esc_url( $pc_url ); ?>"
              alt="<?php echo esc_attr( $pc_photo['alt'] ); ?>"
              <?php if ( ! empty( $pc_photo['w'] ) && ! empty( $pc_photo['h'] ) ) : ?>
              width="<?php echo esc_attr( (string) (int) $pc_photo['w'] ); ?>"
              height="<?php echo esc_attr( (string) (int) $pc_photo['h'] ); ?>"
              <?php endif; ?>
              loading="<?php echo $pc_first ? 'eager' : 'lazy'; ?>"
              decoding="async"
              sizes="(min-width: 64rem) 1000px, 100vw"
            />
            <?php if ( '' !== (string) $pc_photo['caption'] ) : ?>
              <figcaption class="bhp-photo-carousel__caption"><?php echo esc_html( (string) $pc_photo['caption'] ); ?></figcaption>
            <?php endif; ?>
          </figure>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if ( $pc_total > 1 ) : ?>
      <button type="button" class="bhp-photo-carousel__arrow bhp-photo-carousel__arrow--prev" data-bhp-pc-prev hidden>
        <span aria-hidden="true">&#8249;</span>
        <span class="screen-reader-text"><?php esc_html_e( 'Previous photograph', 'brave-hearts' ); ?></span>
      </button>
      <button type="button" class="bhp-photo-carousel__arrow bhp-photo-carousel__arrow--next" data-bhp-pc-next hidden>
        <span aria-hidden="true">&#8250;</span>
        <span class="screen-reader-text"><?php esc_html_e( 'Next photograph', 'brave-hearts' ); ?></span>
      </button>
    <?php endif; ?>
  </div>

  <?php if ( $pc_total > 1 ) : ?>
    <div class="bhp-photo-carousel__controls" data-bhp-pc-dots hidden>
      <ul class="bhp-photo-carousel__dots" role="list">
        <?php for ( $pc_d = 0; $pc_d < $pc_total; $pc_d++ ) : ?>
          <li class="bhp-photo-carousel__dot-item">
            <button type="button"
                    class="bhp-photo-carousel__dot"
                    data-bhp-pc-dot="<?php echo esc_attr( (string) $pc_d ); ?>"
                    aria-current="<?php echo 0 === $pc_d ? 'true' : 'false'; ?>">
              <span class="screen-reader-text">
                <?php
                /* translators: %1$d: photograph number. %2$d: total photographs. */
                printf( esc_html__( 'Show photograph %1$d of %2$d', 'brave-hearts' ), (int) $pc_d + 1, (int) $pc_total );
                ?>
              </span>
            </button>
          </li>
        <?php endfor; ?>
      </ul>
      <p class="bhp-photo-carousel__counter" aria-hidden="true">
        <span data-bhp-pc-current>1</span> / <?php echo esc_html( (string) $pc_total ); ?>
      </p>
    </div>

    <?php /* One polite live region, so a slide change is announced once. */ ?>
    <p class="screen-reader-text" aria-live="polite" data-bhp-pc-status data-bhp-pc-at="0"></p>
  <?php endif; ?>
</div>
