<?php
/**
 * Brave Hearts — reusable "Look Inside" media gallery.
 *
 * ONE component, used by single-book pages and the Complete Collection page.
 * It renders only what has been approved for the title it is given, and
 * renders NOTHING when nothing has been approved.
 *
 * Structure (matches the approved interaction spec):
 *   - a single STAGE that every item shares — video and images alike
 *   - prev / next arrows embedded on the stage
 *   - a THUMBNAIL CAROUSEL below: hover previews an item on the stage,
 *     click locks it in
 *   - the active image can be opened larger ("inspect"); the active video
 *     plays in place through native controls
 *
 * Expects:
 *   $media    array   from bhp_book_media() — REQUIRED
 *   $heading  string  optional heading override
 *   $heading_hidden bool  render the heading visually-hidden (default false)
 *   $intro    string  optional one-line intro
 *   $level    string  heading tag, default h2
 *   $compact  bool    tightens spacing (Collection page)
 *   $gallery_cue string optional pre-escaped HTML rendered below the rail
 *                       (1.19.241 — the product page's flip-through cue)
 *
 * Video rules from the wireframe brief §4.7, all implemented here:
 *   - a real <video>, never a div, never an iframe wrapper
 *   - controls, playsinline, preload="metadata" (no video byte before paint)
 *   - a descriptive poster, so the first frame is never black
 *   - NO autoplay, and no sound without a user gesture
 *   - native controls, so keyboard and screen-reader support are inherited
 *   - explicit width/height so the reserved box cannot shift the page
 *
 * Everything works without JavaScript: all items are in the DOM, the first is
 * visible, and the video plays. JS only adds stage-switching.
 */

defined('ABSPATH') || exit;

if (empty($media) || empty($media['has_any']) || empty($media['items'])) {
    return; // The gate.
}

$items   = $media['items'];
$total   = count($items);
$level   = isset($level) && in_array($level, ['h2', 'h3'], true) ? $level : 'h2';
$heading = isset($heading) && $heading ? $heading : __('Look inside', 'brave-hearts');
$intro   = isset($intro) ? $intro : '';
$compact = !empty($compact);
/*
 * EAGER FIRST SLIDE. Item 0 is loaded eagerly at high fetch priority because
 * in a HERO placement it genuinely is the largest contentful paint. Below the
 * fold that is actively harmful — it fetches a large image at high priority
 * and competes with the page's real LCP element.
 *
 * Default TRUE, so every existing caller (product hero, Collection hero)
 * behaves exactly as before and this option changes nothing for them. The
 * funnel-page placements pass FALSE.
 */
$eager_first = isset($eager_first) ? (bool) $eager_first : true;
/*
 * HERO MODE. The gallery stands in for WooCommerce's native product gallery
 * at the top of the page, so it IS the main product image rather than a
 * section further down. In this mode the heading is kept for assistive
 * technology but hidden visually — a visible "Look inside" title above the
 * product's own cover would read as a second page section, which it is not.
 */
$hero    = !empty($hero);
/*
 * ⭐ 1.19.182 (2026-08-05) — CYCLE144-LD-111. A VISUALLY-HIDDEN HEADING.
 *
 * DEFAULT FALSE, so every existing caller — the three product pages, the
 * Collection page, /books/ and the four funnel pages — renders exactly what
 * it rendered before and this option changes nothing for them.
 *
 * ⛔ WHY HIDING RATHER THAN OMITTING, and this is the whole reason the
 *    option exists instead of an empty string:
 *      1. `$heading` FALLS BACK to "Look inside" when empty (line above), so
 *         passing '' would print a DIFFERENT heading, not no heading.
 *      2. In non-hero mode this section is labelled `aria-labelledby` at the
 *         heading's id. Deleting the element would leave a dangling
 *         reference and the gallery region would lose its accessible name
 *         entirely — a silent accessibility regression traded for ~32px.
 *    Keeping the element with `screen-reader-text` removes it from the
 *    visual layout and from the visual reading order while the accessible
 *    name, the heading outline and the aria reference are all untouched.
 *
 * ⛔ NO CSS IS ADDED. `.screen-reader-text` is the theme's own sitewide
 *    utility (style.css:199) and is position:absolute/1px/clip, so the
 *    element leaves flow completely. The existing
 *    `.bhp-look-inside__title` font/margin rules still match it and are
 *    harmless on an out-of-flow 1px box.
 */
$heading_hidden = !empty($heading_hidden);
$uid     = 'bhp-look-inside-' . sanitize_html_class($media['key']);

$classes = ['bhp-look-inside', 'bhp-media-gallery'];
if ($hero) {
    $classes[] = 'bhp-media-gallery--hero';
}
// Complete Collection hero: inherits that page's cream/forest/gold system.
if (!empty($collection)) {
    $classes[] = 'bhp-media-gallery--collection';
}
if ($compact) {
    $classes[] = 'bhp-look-inside--compact';
}
if ($total < 2) {
    $classes[] = 'bhp-media-gallery--single';
}
?>
<section class="<?php echo esc_attr(implode(' ', $classes)); ?>"
         id="<?php echo esc_attr($uid); ?>"
         <?php
         /*
          * HERO MODE USES aria-label, NOT A HEADING — measured, not stylistic.
          * The hero renders on `woocommerce_before_single_product_summary`, so
          * it precedes the product's <h1>. A visually-hidden <h2> here produced
          * a document outline of H2 → H1 → H2, i.e. a heading before the page's
          * own title. Labelling the region directly names it for assistive
          * technology without inserting anything into the heading outline.
          */
         if ($hero) :
             ?>aria-label="<?php echo esc_attr($heading); ?>"<?php
         else :
             ?>aria-labelledby="<?php echo esc_attr($uid); ?>-title"<?php
         endif;
         ?>
         data-bhp-gallery
         data-bhp-gallery-count="<?php echo esc_attr((string) $total); ?>"
         <?php
         /*
          * ANALYTICS CONTEXT (2026-08-02). These are the SAME generic attribute
          * names `bhpBuildEventPayload()` in `assets/js/nav.js` already reads
          * for every other tracked component — `data-bhp-book`,
          * `data-bhp-format`, `data-bhp-source` — so the gallery's events carry
          * an identical payload shape to the rest of the site and need no new
          * GTM variables beyond the three that already exist.
          *
          * They sit on the SECTION, not on each control, on purpose: they are
          * gallery-level facts. Per-item facts are read off the slide.
          *
          * `data-bhp-event` is deliberately NOT used anywhere in this template
          * — see the header comment in `assets/js/book-media.js` for why one
          * emitter inside the component beats two.
          */
         ?>
         data-bhp-book="<?php echo esc_attr($media['key']); ?>"
         data-bhp-format=""
         data-bhp-source="<?php echo esc_attr($hero ? 'look_inside_hero' : 'look_inside_section'); ?>">

  <?php if (!$hero): ?>
    <<?php echo esc_html($level); ?> class="bhp-look-inside__title<?php echo $heading_hidden ? ' screen-reader-text' : ''; ?>" id="<?php echo esc_attr($uid); ?>-title">
      <?php echo esc_html($heading); ?>
    </<?php echo esc_html($level); ?>>

    <?php if ($intro): ?>
      <p class="bhp-look-inside__intro"><?php echo esc_html($intro); ?></p>
    <?php endif; ?>
  <?php endif; ?>

  <div class="bhp-gallery">

    <!-- ================= STAGE ================= -->
    <div class="bhp-gallery__stage" data-bhp-gallery-stage>

      <?php foreach ($items as $i => $item): ?>
        <div class="bhp-gallery__slide<?php echo 0 === $i ? ' is-active' : ''; ?>"
             data-bhp-slide="<?php echo esc_attr((string) $i); ?>"
             data-bhp-slide-type="<?php echo esc_attr($item['type']); ?>"
             <?php /* Analytics only — the rail's visual grouping already uses
                      $item['group'] below; this just makes it readable per
                      slide so an event can say which title it belonged to. */ ?>
             data-bhp-slide-group="<?php echo esc_attr(isset($item['group']) ? $item['group'] : ''); ?>"
             <?php echo 0 === $i ? '' : 'hidden'; ?>>

          <?php if ('video' === $item['type']): ?>

            <?php
            /*
             * LAZY MOUNTING. Only the initially-active video gets a real
             * `poster` and real `src` attributes. Every other video ships its
             * URLs in data-* and is mounted by JS the first time its slide
             * becomes active.
             *
             * Why: the Complete Collection stage holds several videos. Emitting
             * three posters and three source sets would have the browser fetch
             * three large images (and metadata for three clips) before the
             * visitor has asked for any of them. On single-book pages this also
             * stops the one video's poster loading when slide 1 is the cover.
             */
            $mounted = (0 === $i);
            ?>
            <video class="bhp-gallery__video"
                   controls
                   playsinline
                   preload="metadata"
                   width="1280"
                   height="720"
                   <?php if ($mounted): ?>poster="<?php echo esc_url($item['poster']); ?>"<?php endif; ?>
                   data-bhp-poster="<?php echo esc_url($item['poster']); ?>"
                   aria-label="<?php echo esc_attr($item['label']); ?>"
                   data-bhp-gallery-video
                   <?php echo $mounted ? 'data-bhp-mounted="1"' : ''; ?>>
              <?php /* WebM first: VP9-capable browsers take the smaller file. */ ?>
              <?php if ($item['webm']): ?>
                <source <?php echo $mounted ? 'src' : 'data-bhp-src'; ?>="<?php echo esc_url($item['webm']); ?>" type="video/webm">
              <?php endif; ?>
              <?php if ($item['mp4']): ?>
                <source <?php echo $mounted ? 'src' : 'data-bhp-src'; ?>="<?php echo esc_url($item['mp4']); ?>" type="video/mp4">
              <?php endif; ?>
              <?php esc_html_e('Your browser cannot play this video.', 'brave-hearts'); ?>
              <?php if ($item['mp4']): ?>
                <a href="<?php echo esc_url($item['mp4']); ?>"><?php esc_html_e('Download the flip-through instead.', 'brave-hearts'); ?></a>
              <?php endif; ?>
            </video>

          <?php else: ?>

            <?php
            /*
             * The stage image is a BUTTON so "inspect" is reachable by
             * keyboard and announced as an action, not just a picture.
             *
             * LCP: in hero mode the FIRST item is the main product image and
             * therefore the largest contentful paint. It loads eagerly at high
             * fetch priority; every other slide stays lazy so switching costs
             * nothing up front. All slides carry explicit dimensions via
             * wp_get_attachment_image(), which is what keeps layout shift at
             * zero when the stage swaps.
             */
            $is_first = (0 === $i);
            $img_attrs = [
                'class'    => 'bhp-gallery__img',
                'decoding' => 'async',
                'alt'      => $item['alt'],
                /*
                 * F7 / CYCLE142-LD-01 — AN EXPLICIT, RESOLVABLE `sizes`.
                 *
                 * MEASURED, not guessed: the stage renders 358 CSS px wide at
                 * a 390 px viewport and 571 CSS px wide at 1440 (live on
                 * staging 1.19.149, `getBoundingClientRect()` with
                 * `innerWidth` asserted on both runs).
                 *
                 * WHY THIS MATTERS FAR MORE THAN IT LOOKS. Every non-first
                 * slide is emitted `hidden` + `loading="lazy"`, and WordPress
                 * 7.0 prepends `auto, ` to a lazy image's `sizes`
                 * (wp-includes/media.php:1166). A hidden slide has NO layout
                 * box, so `auto` cannot resolve; the browser falls back to the
                 * next clause — `100vw` — computes 390 x DPR 3 = 1170 px
                 * required, and downloads the LARGEST srcset candidate, i.e.
                 * the full-size original, FOR AN IMAGE THAT IS NO LONGER ON
                 * SCREEN. Measured cost of a full 9-slide walk on
                 * /complete-collection/ at 390 px: 2,191 KB shipped versus
                 * 1,040 KB with a fixed `sizes` — 1,151 KB (52%) of pure
                 * waste, on the primary purchase page, on a phone.
                 *
                 * A fixed value cannot become unresolvable, so the selection a
                 * slide makes while visible is the same selection it keeps
                 * while hidden, and the re-fetch cannot happen at all.
                 *
                 * The `auto, ` prefix is stripped in `inc/book-media.php` —
                 * setting `sizes` here is not enough on its own, because
                 * WordPress prepends to whatever value it finds.
                 */
                'sizes'    => '(max-width: 1024px) 300px, 560px',
            ];
            if ($is_first && $eager_first) {
                $img_attrs['loading']       = 'eager';
                $img_attrs['fetchpriority'] = 'high';
            } else {
                $img_attrs['loading'] = 'lazy';
            }
            ?>
            <button type="button"
                    class="bhp-gallery__inspect"
                    data-bhp-gallery-inspect
                    data-bhp-full="<?php echo esc_url((string) wp_get_attachment_image_url($item['id'], 'full')); ?>"
                    data-bhp-alt="<?php echo esc_attr($item['alt']); ?>">
              <?php
              /*
               * F7 / CYCLE142-LD-01 — WordPress's `auto` sizes is turned OFF
               * for exactly these images, and for the duration of exactly this
               * one call.
               *
               * `wp_img_tag_add_auto_sizes` is WordPress's own documented
               * switch (wp-includes/media.php:1156). It is added immediately
               * before the call and REMOVED immediately after, so no other
               * image anywhere on the page, in any plugin or in post content,
               * loses auto-sizes as a side effect.
               *
               * TWO EARLIER ATTEMPTS FAILED AND ARE RECORDED HERE RATHER THAN
               * QUIETLY REPLACED, because "it should work" is exactly the
               * reasoning that produced the defect being fixed. Both were
               * verified live and both left the rendered attribute as
               * `sizes="auto, (max-width: 1024px) 360px, 580px"` with the
               * 9-slide walk still costing 1,851 KB, identical to production:
               *
               *   1. stripping the prefix on `wp_get_attachment_image_attributes`
               *   2. toggling `wp_img_tag_add_auto_sizes` around THIS call
               *
               * Both target `wp_get_attachment_image()`. The prefix is not
               * (only) coming from there. This page renders through a SHORTCODE
               * inside `the_content`, so `wp_filter_content_tags()` runs
               * `wp_img_tag_add_auto_sizes()` over the finished markup a second
               * time (wp-includes/media.php:1960) and re-adds it. The switch
               * therefore has to be off for the whole request, which is what
               * `bhp_disable_auto_sizes_on_gallery_pages()` in
               * `inc/book-media.php` does — scoped to the page types that
               * actually carry a gallery.
               */
              echo wp_get_attachment_image($item['id'], 'large', false, $img_attrs);
              ?>
              <span class="bhp-gallery__inspect-hint" aria-hidden="true">
                <?php esc_html_e('Click to enlarge', 'brave-hearts'); ?>
              </span>
              <span class="screen-reader-text"><?php esc_html_e('Enlarge this image', 'brave-hearts'); ?></span>
            </button>

          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <?php if ($total > 1): ?>
        <button type="button" class="bhp-gallery__arrow bhp-gallery__arrow--prev" data-bhp-gallery-prev>
          <span aria-hidden="true">&#8249;</span>
          <span class="screen-reader-text"><?php esc_html_e('Previous item', 'brave-hearts'); ?></span>
        </button>
        <button type="button" class="bhp-gallery__arrow bhp-gallery__arrow--next" data-bhp-gallery-next>
          <span aria-hidden="true">&#8250;</span>
          <span class="screen-reader-text"><?php esc_html_e('Next item', 'brave-hearts'); ?></span>
        </button>
      <?php endif; ?>
    </div>

    <?php
    /*
     * ⭐ 1.19.262 (2026-08-19, `CYCLE165-LD-DIRECTION1-STEP3-PRODUCT`) — THE
     *    SLIDE COUNTER LEAVES THE PICTURE.
     *
     * ⭐ THE DEFECT, OBSERVED RATHER THAN REASONED. Headless Chrome at an
     *    asserted `innerWidth` of 390 on staging2 1.19.261: the counter pill
     *    rendered at top 402 inside a stage running 199 to 590, i.e. squarely
     *    over the bottom band of the cover artwork — which on all three covers
     *    is where "Big Places. Brave Hearts." and Andrew's name are printed.
     *    The one line on the cover that says who wrote the book was covered by
     *    a slide number, on every book page, at both widths. Screenshot in the
     *    step-3 QA evidence.
     *
     * ⛔ THE FIX IS TO MOVE IT OUT OF THE STAGE, not to restyle it inside. The
     *    stage is `overflow: hidden`, so an absolutely-positioned pill can only
     *    ever sit ON the artwork; nudging it to a different corner trades one
     *    covered thing for another. As a caption UNDER the stage it covers
     *    nothing at any viewport, on any cover, forever.
     *
     * ⛔ THE HOOK IS UNCHANGED. `assets/js/book-media.js` finds the live number
     *    with `root.querySelector('[data-bhp-gallery-current]')`, where `root`
     *    is the gallery SECTION — so it still resolves one level up, and the
     *    counter still updates on every slide change. `data-bhp-gallery-counter`
     *    and `aria-hidden` are carried across untouched.
     *
     * ⛔ IT STILL ONLY RENDERS FOR A MULTI-ITEM GALLERY, and the single-item
     *    rule in `book-media.css` still hides it, so nothing changed for a
     *    one-picture product.
     */
    ?>
    <?php if ($total > 1): ?>
      <p class="bhp-gallery__counter" data-bhp-gallery-counter aria-hidden="true">
        <span data-bhp-gallery-current>1</span> / <?php echo esc_html((string) $total); ?>
      </p>
    <?php endif; ?>

    <?php /* One polite live region, so switching items is announced once. */ ?>
    <p class="screen-reader-text" aria-live="polite" data-bhp-gallery-status></p>

    <!-- ================= THUMBNAIL CAROUSEL ================= -->
    <?php if ($total > 1): ?>
      <ul class="bhp-gallery__thumbs" role="list" data-bhp-gallery-thumbs>
        <?php
        $prev_group = null;
        foreach ($items as $i => $item):
            $group = isset($item['group']) ? $item['group'] : '';
            /*
             * A group starts a new visual cluster in the rail. The visible
             * caption is decorative and hidden from assistive technology,
             * because every thumb already carries its title in its own
             * accessible name — announcing the group again would just repeat.
             * This is NOT a tab set: nothing here is focusable or selectable,
             * and no item is filtered by it.
             */
            $starts_group = ('' !== $group && $group !== $prev_group);
            $prev_group = $group;
            $item_classes = ['bhp-gallery__thumb-item'];
            if ($starts_group) {
                $item_classes[] = 'bhp-gallery__thumb-item--group-start';
            }
        ?>
          <li class="<?php echo esc_attr(implode(' ', $item_classes)); ?>">
            <?php if ($starts_group): ?>
              <span class="bhp-gallery__group-label" aria-hidden="true"><?php echo esc_html($group); ?></span>
            <?php endif; ?>
            <?php
            /*
             * ⭐ 1.19.262 (2026-08-19, `CYCLE165-LD-DIRECTION1-STEP3-PRODUCT`) —
             *    THE THUMBNAIL RAIL GAINS REAL ALT TEXT.
             *
             * ⭐ THE FINDING, COUNTED ON THE LIVE PAGES rather than inferred:
             *    every image with a missing `alt` on the four product pages at
             *    390 was a `.bhp-gallery__thumb-img` — 7 on Mariana, 8 on
             *    Everest, 5 on The Amazon. That is CRO rubric row 14, and the
             *    8 the audit reported is the Everest page.
             *
             * ⭐⭐ THE TEXT IS NOT WRITTEN HERE, AND THAT IS THE WHOLE POINT.
             *    Every image in `inc/book-media.php` ALREADY carries a real,
             *    authored `alt` — "Interior spread from the chapter The Whale,
             *    with a pencil illustration of a humpback whale…" — and every
             *    video already carries a `label`. The registry has been the
             *    source of truth for the stage image all along; the rail simply
             *    passed `alt=""` and threw it away. This reads the same string
             *    the stage reads. Nothing is derived from a title, nothing is
             *    generated, and a new item added to the registry is described
             *    in the rail the day it is added, with no edit here.
             *
             * ⚠ THE STAGE AND THE THUMB THEREFORE SHARE ONE STRING. That is
             *   correct: they are the same picture. A second, shorter,
             *   hand-written variant would be a second description of one image
             *   that can drift from the first.
             *
             * ⛔ WHY `alt` WAS EMPTY AND WHY THAT WAS ONCE DEFENSIBLE: the
             *    button already carries a `.screen-reader-text` name, so an
             *    `alt` on the image inside it would once have been read twice.
             *    That reasoning holds for a screen reader and does NOT hold for
             *    the other things alt text serves — an image that fails to load,
             *    a text-only client, or an automated audit, all of which see an
             *    unlabelled tile. The accessible NAME of the button is
             *    unchanged; the image now describes itself as well.
             */
            $thumb_alt = ('video' === $item['type'])
                ? (isset($item['label']) ? (string) $item['label'] : '')
                : (isset($item['alt']) ? (string) $item['alt'] : '');
            ?>
            <button type="button"
                    class="bhp-gallery__thumb"
                    data-bhp-gallery-thumb="<?php echo esc_attr((string) $i); ?>"
                    aria-current="<?php echo 0 === $i ? 'true' : 'false'; ?>">
              <?php if ('video' === $item['type']): ?>
                <?php /* A real frame of the book, not a blank tile — with a
                         small play badge over it so it still reads as video. */ ?>
                <img class="bhp-gallery__thumb-img"
                     src="<?php echo esc_url($item['thumb']); ?>"
                     alt="<?php echo esc_attr($thumb_alt); ?>"
                     loading="lazy"
                     decoding="async"
                     width="120" height="120">
                <span class="bhp-gallery__thumb-play" aria-hidden="true">
                  <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M8 5.5v13l11-6.5z" fill="currentColor"/></svg>
                </span>
              <?php else: ?>
                <?php
                echo wp_get_attachment_image($item['id'], 'thumbnail', false, [
                    'class'    => 'bhp-gallery__thumb-img',
                    'loading'  => 'lazy',
                    'decoding' => 'async',
                    'alt'      => $thumb_alt,
                ]);
                ?>
              <?php endif; ?>

              <?php /* Selected state must not rely on colour alone. */ ?>
              <span class="bhp-gallery__thumb-mark" aria-hidden="true">&#10003;</span>

              <span class="screen-reader-text">
                <?php
                /*
                 * A gallery can hold more than one video, so a generic "Show
                 * the flip-through video" would announce two thumbs
                 * identically. Each video is named by its own label instead.
                 * Where items are grouped by title, the group name is folded
                 * into the button's own name — that is what makes the visible
                 * caption safe to hide from assistive technology.
                 */
                if ('video' === $item['type']) {
                    /* translators: %s: the video's descriptive label. */
                    printf(esc_html__('Play video: %s', 'brave-hearts'), esc_html($item['label']));
                } elseif ('' !== $group) {
                    /* translators: %1$s: title group. %2$d: item number. %3$d: total items. */
                    printf(
                        /*
                         * Wave F (2026-08-03), item 11. SCOPE NOTE, flagged
                         * rather than buried: this string and the note below
                         * are the only two em dashes the homepage renders that
                         * do NOT live in homepage copy -- they belong to the
                         * SHARED look-inside component, which also renders on
                         * the three product pages, /books/ and the funnel
                         * pages. Removing the dash therefore changes those
                         * pages' punctuation too.
                         *
                         * Done anyway, and reported, because it is mechanical
                         * punctuation with an identical meaning and no claim
                         * attached, and leaving it would mean the homepage
                         * still rendered em dashes after an instruction to
                         * remove every one. Reverting is a two-string change.
                         */
                        esc_html__('%1$s: show image %2$d of %3$d', 'brave-hearts'),
                        esc_html($group),
                        (int) $i + 1,
                        (int) $total
                    );
                } else {
                    /* translators: %1$d: item number. %2$d: total items. */
                    printf(esc_html__('Show image %1$d of %2$d', 'brave-hearts'), (int) $i + 1, (int) $total);
                }
                ?>
              </span>
            </button>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <?php
  /*
   * ⭐ 1.19.241 (2026-08-18, `CYCLE164-LD-STOREFRONT-BATCH`) — OPTIONAL CUE SLOT.
   *
   * ⛔ DEFAULT EMPTY, SO NOTHING ELSE MOVES. All eight existing callers — the
   *    three product heroes, the Collection hero, /books/ and the four funnel
   *    pages — pass nothing and render byte-identical markup to before. Only
   *    `bhp_book_render_hero_gallery()` fills it.
   *
   * ⛔ IT LIVES INSIDE THIS <section> DELIBERATELY. `div.product` is a CSS grid
   *    (`assets/css/book-media.css:401`), so emitting the cue as a THIRD grid
   *    child beside the gallery and the summary would have handed it its own
   *    grid cell and re-flowed the purchase column. Inside the section it is
   *    the gallery's own child and the grid sees exactly what it saw before.
   *
   * ⛔ ECHOED UNESCAPED, AND THAT IS THE CONTRACT: the caller passes markup it
   *    has already escaped. It is never fed anything from a request, an option
   *    or a database field — see bhp_book_render_flip_through_cue(), which
   *    builds every attribute through esc_attr()/esc_html() and is the only
   *    producer of this value.
   */
  if (!empty($gallery_cue)) {
      echo $gallery_cue; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped by the caller; see above.
  }
  ?>

  <?php
  /*
   * Honest scope note. The brief caps what may be shown so the preview never
   * amounts to a substantial portion of the book, and saying so plainly also
   * pre-empts "is this the whole book?" as a support question.
   */
  ?>
  <p class="bhp-look-inside__note">
    <?php /* Wave F item 11 -- shared-component em dash; see the scope note on
             the "show image N of M" string above. */ ?>
    <?php esc_html_e('A short preview of the printed edition. A few pages only.', 'brave-hearts'); ?>
  </p>

  <?php /* Lightbox. Empty until opened; removed from the a11y tree when closed. */ ?>
  <div class="bhp-gallery__lightbox" data-bhp-gallery-lightbox hidden>
    <div class="bhp-gallery__lightbox-backdrop" data-bhp-gallery-lightbox-close></div>
    <div class="bhp-gallery__lightbox-panel" role="dialog" aria-modal="true"
         aria-label="<?php esc_attr_e('Enlarged image', 'brave-hearts'); ?>">
      <button type="button" class="bhp-gallery__lightbox-close" data-bhp-gallery-lightbox-close>
        <span aria-hidden="true">&times;</span>
        <span class="screen-reader-text"><?php esc_html_e('Close', 'brave-hearts'); ?></span>
      </button>
      <?php /* No `src` attribute until opened: an empty src is a broken image
               to the browser and can fire a spurious request to the page URL. */ ?>
      <img class="bhp-gallery__lightbox-img" data-bhp-gallery-lightbox-img alt="">
    </div>
  </div>
</section>
