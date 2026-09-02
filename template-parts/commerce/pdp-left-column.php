<?php
/**
 * Brave Hearts — the PDP left column, phase 2 of the Concept A redesign.
 *
 * "Look inside" plates, then "What is inside" bullets. ONE partial, five
 * surfaces: the three chapter books (one page each, serving both paperback and
 * hardcover), the colouring book, and the Complete Collection page.
 *
 * `CYCLE179-LD-349` · theme 1.19.349 · founder seal 679 (Concept A).
 *
 * Expects:
 *   $bhp_pdp_key      string  content key from bhp_pdp_content_key() — REQUIRED
 *   $bhp_pdp_context  string  'product' | 'collection'
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * ⛔ WHAT IS NOT IN THIS FILE, AND WHY EACH ABSENCE IS DELIBERATE
 *
 *   · NO rating, review count, star glyph, Kirkus pull or any other proof line.
 *     The `chief-of-staff` brief rules them OFF until `ACT-OPS-333` clears,
 *     `BOR-003`
 *     leaves the ratings count UNVERIFIED pending a live per-title count with a
 *     date, and `BOR-005` leaves the Kirkus disclosure wording with Andrew.
 *     ⛔ "Off" means "do not add". The existing `amazon-review-card__quote`
 *        section is a PROTECTED element (`21-PROTECTED-ELEMENTS-MANIFEST.md`
 *        §3.3, min 1) and is not touched by this file in either direction.
 *
 *   · NO caption text inside the images. Page numbers live in the alt text and
 *     in a real `<figcaption>`, where a screen reader and a search engine can
 *     both read them (`design-creative` handling note 4).
 *
 *   · NO crop. The white margins in these files are the real printed margins;
 *     cropping them changes what the page looks like and breaks the alt text
 *     (handling note 3). `object-fit` is never applied to a plate.
 *
 *   · NO "spread" in any visible string on the colouring surface. See
 *     `bhp_pdp_look_inside_noun()` — those two plates are not facing pages.
 *
 *   · NO em dash and NO en dash anywhere in this file's own strings.
 * ───────────────────────────────────────────────────────────────────────────── */

defined('ABSPATH') || exit;

$bhp_pdp_key = isset($bhp_pdp_key) ? (string) $bhp_pdp_key : '';
if ('' === $bhp_pdp_key) {
    return; // The gate.
}
$bhp_pdp_context = isset($bhp_pdp_context) ? (string) $bhp_pdp_context : 'product';

$bhp_pdp_registry = bhp_pdp_look_inside_registry();
$bhp_pdp_plates   = isset($bhp_pdp_registry[$bhp_pdp_key]) ? $bhp_pdp_registry[$bhp_pdp_key] : [];
$bhp_pdp_bullets  = bhp_book_whats_inside($bhp_pdp_key);

if (empty($bhp_pdp_plates) && empty($bhp_pdp_bullets)) {
    return;
}

$bhp_pdp_base   = get_stylesheet_directory_uri() . '/assets/look-inside/';
$bhp_pdp_noun   = bhp_pdp_look_inside_noun($bhp_pdp_key);
$bhp_pdp_uid    = 'bhp-pdp-' . sanitize_html_class($bhp_pdp_key);

/*
 * ⭐ `sizes` IS PER SURFACE, AND IT IS A MEASUREMENT RATHER THAN AN ESTIMATE.
 *    Read off the rendered pages on staging 1.19.349 with `innerWidth`
 *    asserted, `getBoundingClientRect()` on the plate itself:
 *
 *        product page,  1440 -> 593px   ·  1366 -> 594px  (one plate per row)
 *        collection,    1440 -> 356px   ·  1366 -> 356px  (three across)
 *        either,         375 -> 343px   (= 100vw - 32px)
 *
 * ⛔ THE FIRST VERSION SHIPPED ONE STRING FOR BOTH AND THE COLLECTION PAGE
 *    PAID FOR IT: declaring 590px for a box that renders 356px made the
 *    browser fetch the 1200px file (about 130KB each, three of them) where the
 *    800px file is already more than the box can use. Caught by reading
 *    `img.currentSrc` off the live page, not by reasoning about the attribute.
 *
 * ⛔ A `sizes` THAT LIES IS WORSE THAN A CRUDE ONE, in both directions: too
 *    large over-fetches, too small ships a soft image to a retina screen.
 *    These are the real numbers, so it does neither.
 */
$bhp_pdp_sizes = ('collection' === $bhp_pdp_context)
    ? '(max-width: 900px) calc(100vw - 32px), 360px'
    : '(max-width: 900px) calc(100vw - 32px), 590px';
?>
<div class="bhp-pdp-left bhp-pdp-left--<?php echo esc_attr($bhp_pdp_context); ?>" data-bhp-pdp-key="<?php echo esc_attr($bhp_pdp_key); ?>">

  <?php if (!empty($bhp_pdp_plates)) : ?>
  <section class="bhp-pdp-look-inside" aria-labelledby="<?php echo esc_attr($bhp_pdp_uid); ?>-li">
    <h2 class="bhp-pdp-section__title" id="<?php echo esc_attr($bhp_pdp_uid); ?>-li">
      <?php esc_html_e('Look inside', 'brave-hearts'); ?>
    </h2>

    <div class="bhp-pdp-look-inside__grid">
      <?php foreach ($bhp_pdp_plates as $bhp_i => $bhp_plate) : ?>
        <?php
        $bhp_stem = $bhp_plate['stem'];
        /*
         * ⭐ srcset AT THE THREE WIDTHS THE DESIGN LANE DELIVERED, AND A
         *    `sizes` THAT
         *    MATCHES THE REAL COLUMN. Concept A's gallery column is ~640px at
         *    1440; two plates sit side by side inside it on the collection
         *    surface and stack on a product page, so the honest widths are
         *    ~590 desktop and ~343 phone. ⛔ The 1600px file is for a 2x
         *    column, never for full bleed.
         *
         * ⛔ EXPLICIT `width`/`height` ON EVERY PLATE. These are below the
         *    fold and lazy, and a lazy image with no intrinsic box is a layout
         *    shift waiting for a slow connection. The numbers are the real
         *    1600px file dimensions from the `design-creative` manifest,
         *    md5-verified
         *    into the theme this build, so the aspect ratio is the printed
         *    page's and nothing is squashed.
         *
         * ⛔ `loading="lazy"` ON EVERY PLATE, WITHOUT EXCEPTION. The LCP
         *    element on this template is the cover in the gallery above, which
         *    already carries `eager` + `fetchpriority="high"`. A second eager
         *    image competes with it for the same connection, which is the
         *    defect web.dev's LCP guidance is about and the one the
         *    `ads-knowledge` checklist row 11 asks us not to introduce.
         */
        ?>
        <figure class="bhp-pdp-plate">
          <img class="bhp-pdp-plate__img"
               src="<?php echo esc_url($bhp_pdp_base . $bhp_stem . '-1200.jpg'); ?>"
               srcset="<?php echo esc_attr(
                   $bhp_pdp_base . $bhp_stem . '-800.jpg 800w, '
                   . $bhp_pdp_base . $bhp_stem . '-1200.jpg 1200w, '
                   . $bhp_pdp_base . $bhp_stem . '-1600.jpg 1600w'
               ); ?>"
               sizes="<?php echo esc_attr($bhp_pdp_sizes); ?>"
               width="<?php echo esc_attr((string) $bhp_plate['w']); ?>"
               height="<?php echo esc_attr((string) $bhp_plate['h']); ?>"
               loading="lazy"
               decoding="async"
               alt="<?php echo esc_attr($bhp_plate['alt']); ?>">
          <figcaption class="bhp-pdp-plate__caption">
            <?php echo esc_html(sprintf($bhp_pdp_noun, $bhp_plate['pages'])); ?>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>

    <?php
    /*
     * The same honest scope note the shared `look-inside.php` component
     * carries, for the same reason: the preview never amounts to a substantial
     * portion of the book, and saying so plainly also pre-empts "is this the
     * whole book?" as a support question. ⛔ Reworded here WITHOUT the em dash
     * that string carries, because this is a NEW string and rule 608a applies
     * to new strings. The old one is not edited: it is a shared component that
     * also renders on the homepage and four funnel pages.
     */
    ?>
    <p class="bhp-pdp-look-inside__note">
      <?php esc_html_e('A short preview of the printed edition. A few pages only.', 'brave-hearts'); ?>
    </p>
  </section>
  <?php endif; ?>

  <?php if (!empty($bhp_pdp_bullets)) : ?>
  <section class="bhp-pdp-whats-inside" aria-labelledby="<?php echo esc_attr($bhp_pdp_uid); ?>-wi">
    <h2 class="bhp-pdp-section__title" id="<?php echo esc_attr($bhp_pdp_uid); ?>-wi">
      <?php esc_html_e('What is inside', 'brave-hearts'); ?>
    </h2>
    <ul class="bhp-pdp-whats-inside__list">
      <?php foreach ($bhp_pdp_bullets as $bhp_bullet) : ?>
        <?php /* Escaped, and the strings carry no inline HTML by contract. */ ?>
        <li class="bhp-pdp-whats-inside__item"><?php echo esc_html($bhp_bullet); ?></li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>

</div>
