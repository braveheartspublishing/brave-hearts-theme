<?php
/**
 * Template Name: Read-Aloud Take-Home Landing Page
 * Description: The destination of the dynamic QR code printed at the foot of
 * the coloring page every child takes home from one of Andrew's elementary
 * read-aloud visits. One page serves EVERY visit, forever — it is
 * school-agnostic by design and never goes stale.
 *
 * ⛔ READ `inc/read-aloud-landing.php` FIRST. Every decision behind this page
 *    lives there: why it is school-agnostic, why it shows BOTH covers, why the
 *    capture is the existing parent funnel with only a new context, and the
 *    copy rails below are enforced against.
 *
 * ⛔ THE COPY RAILS, RESTATED HERE BECAUSE THIS IS THE FILE SOMEONE WILL EDIT:
 *      · Andrew's I-voice. NO "we", "us" or "our" in any visible string.
 *      · NO school name, date, grade, teacher or city, ever.
 *      · NO review, rating, testimonial, reaction, result, statistic or award.
 *      · NO price LITERAL in any string here. ⚠ 1.19.291: the combo DOES show
 *        a price, and it is the offer engine's, re-read live on every render.
 *        The rail is against typing a figure into copy where it can go stale —
 *        not against the engine printing its own. Superseded wording, kept so
 *        it is not re-derived: *"NO price. Every commercial control here is a
 *        LINK."* Full reasoning in `inc/read-aloud-landing.php`.
 *      · AMERICAN SPELLING in every customer-facing string — "coloring", never
 *        "colouring". Founder standing rule, 2026-08-24. The British forms that
 *        remain are PHP identifiers, a CSS modifier and a media slug; none is
 *        text a customer reads, and the suite draws that line for you.
 *      · Reading age 6–9. NEVER 5–9.
 *      · NO chapter count, page count or design count — those numbers belong
 *        to the product pages, and a second copy is a second thing to keep true.
 *
 * ⚠ THE READER IS A PARENT ON A PHONE. The mobile layout is the primary one,
 *   not the fallback: this page is reached by scanning a printed sheet, so a
 *   desktop visit is the rare case. The stylesheet is written mobile-first for
 *   that reason.
 */

defined('ABSPATH') || exit;

get_header();

$page_id     = get_queried_object_id();
$source_page = get_permalink($page_id) ?: home_url('/');
$download    = function_exists('bhp_get_reluctant_reader_download')
    ? bhp_get_reluctant_reader_download()
    : ['url' => '', 'ready' => false];
$pair        = bhp_read_aloud_continuity_pair();
$shop_url    = home_url('/shop/');

/*
 * ⭐⭐ THE COMBO — 1.19.291, Andrew's second amendment after his staging walk.
 *    *"add the combo of both of them as well … It should be the first option
 *    as well"*.
 *
 * ⛔ RESOLVED BEFORE ANY COPY IS PRINTED. `bhp_read_aloud_combo()` returns []
 *    when the existing offer engine says the pair cannot be bought here and
 *    now — and every line of the combo block below is inside that condition.
 *    Nothing is advertised that cannot be bought (`R1.4`).
 * ⛔ NO PRODUCT ID AND NO PRICE APPEARS IN THIS TEMPLATE. Both live in the
 *    offer engine and are re-read on every render.
 */
$combo = function_exists('bhp_read_aloud_combo') ? bhp_read_aloud_combo() : [];

/*
 * Landing-page impression event — the same gate and the same "fire once per
 * real page load" shape as `parent_landing_view` on the Adventure Kit page.
 * ⛔ NO PII, no visit slug, no school. `funnel => parent` because that is the
 *    funnel this page feeds; the read-aloud origin travels in `page_type`, not
 *    in a second funnel identity.
 */
if (class_exists('BHP_Analytics_Config') && BHP_Analytics_Config::should_render_analytics()):
    $bhp_read_aloud_payload = wp_json_encode([
        'event'      => 'read_aloud_landing_view',
        'funnel'     => 'parent',
        'page_type'  => 'read_aloud_landing',
        'lead_offer' => 'reluctant_reader_adventure_kit',
        'audience'   => 'parents_families',
    ]);
    ?>
    <script>
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(<?php echo $bhp_read_aloud_payload; ?>);
    </script>
    <?php
endif;

/*
 * The two continuity tiles' words. ⭐ The LABEL is what makes each cover mean
 * something — a cover with no caption is decoration; a cover captioned "the
 * book I read from" is continuity. Kept beside each other here so the pair can
 * never be described inconsistently.
 */
$tile_copy = [
    'chapter' => [
        'eyebrow' => __('The story I read', 'brave-hearts'),
        'title'   => __('Adventures of Charlotte &amp; Henry: The Mariana Trench', 'brave-hearts'),
        'text'    => __('This is the book the story came from — Charlotte and Henry diving to the deepest place on Earth.', 'brave-hearts'),
        'cta'     => __('See the book', 'brave-hearts'),
    ],
    'colouring' => [
        'eyebrow' => __('Your coloring page', 'brave-hearts'),
        'title'   => __('Coloring Adventures with Charlotte &amp; Henry: The Mariana Trench', 'brave-hearts'),
        'text'    => __('The page you brought home came from this coloring book — the same ocean, the same adventure, ready to color.', 'brave-hearts'),
        'cta'     => __('See the coloring book', 'brave-hearts'),
    ],
];
?>

<main id="primary" class="read-aloud" role="main">

  <?php
  /*
   * ═════════════ KID CONTINUITY — the first screen ═════════════
   *
   * ⛔ THIS IS A PHP COMMENT AND NOT AN HTML ONE, DELIBERATELY, AND EVERY
   *    OTHER NOTE ON THIS PAGE FOLLOWS THE SAME RULE. 1.19.293
   *    (`CYCLE166-CX-ANNOTATION-STRIP`) converted five HTML comments in this
   *    file into PHP comments. An HTML comment is SERVED — it reaches every
   *    visitor and every crawler that views source. A PHP comment never
   *    leaves the server. The notes were worth keeping; shipping them was
   *    not. Do not convert any of them back.
   *
   * ⛔ This heading is written to be read ALOUD BY THE PARENT TO THE CHILD
   *    standing next to them. It is the only place on the page where the
   *    child is the addressee; everything below it speaks to the parent.
   *    Do not "professionalise" it.
   */
  ?>
  <section class="read-aloud-hero">
    <div class="read-aloud__inner">
      <p class="read-aloud-hero__eyebrow"><?php esc_html_e('Welcome, explorer', 'brave-hearts'); ?></p>
      <h1 class="read-aloud-hero__title"><?php esc_html_e('You met Charlotte &amp; Henry today!', 'brave-hearts'); ?></h1>
      <p class="read-aloud-hero__lead">
        <?php esc_html_e('I’m Andrew, and I read with your class today. Charlotte and Henry go to the biggest, wildest places on Earth — and the adventure doesn’t stop when the reading does.', 'brave-hearts'); ?>
      </p>
      <p class="read-aloud-hero__hand">
        <?php esc_html_e('Keep coloring. Then turn the page and see where they go next.', 'brave-hearts'); ?>
      </p>
    </div>
  </section>

  <?php
  /*
   * ═════════════ BOTH COVERS — the founder amendment ═════════════
   * ⭐ "Show both the MT book and the MT coloring book not just the coloring
   *    book" (Andrew, relayed through the Chief of Staff).
   * ⛔ Each tile draws its OWN cover. A tile whose cover does not resolve
   *    renders WITHOUT a picture and NEVER borrows its sibling's — see the
   *    `FD-549` note in inc/read-aloud-landing.php.
   */
  ?>
  <section class="read-aloud-pair" aria-labelledby="read-aloud-pair-title">
    <div class="read-aloud__inner">
      <h2 id="read-aloud-pair-title" class="read-aloud__section-title">
        <?php esc_html_e('Two books, one adventure', 'brave-hearts'); ?>
      </h2>
      <p class="read-aloud__section-lead">
        <?php esc_html_e('One to read together. One to color. They’re the same story, the same ocean, the same two explorers.', 'brave-hearts'); ?>
      </p>

      <?php
      /*
       * ═════════════ THE COMBO — FIRST, BY FOUNDER RULING ═════════════
       * ⭐ "It should be the first option as well" (Andrew, relayed). It sits
       *    ABOVE the two individual cards and inside this section rather than
       *    in one of its own, because the section's own lead — "One to read
       *    together. One to color." — is precisely the sentence that makes
       *    "get both" the obvious answer. Splitting them would separate the
       *    question from the answer.
       *
       * ⛔ EVERYTHING HERE IS INSIDE THE `$combo` GUARD, INCLUDING THE
       *    HEADING, THE PICTURE AND THE "or pick just one" LINE. An empty
       *    `$combo` means the offer is not buyable on this environment or in
       *    this session (a school-visit-flagged session, for one), and the
       *    page must then read as though the combo had never been designed —
       *    two tiles, no orphaned divider, no heading over nothing.
       *
       * ⛔ THE CONTROL IS THE SHOP'S OWN CARD, NOT A COPY OF IT. Pressing ADD
       *    TO CART opens the cart side panel, exactly as it does in the shop
       *    grid. No new pricing path, no new nonce, no new endpoint.
       */
      ?>
      <?php if (!empty($combo)) : ?>
        <div class="read-aloud-combo" data-bhp-card-kind="bundle">
          <?php if ('' !== $combo['art']) : ?>
            <?php
            /*
             * ⛔ R2.3 / FD-549 — DEGRADE, NEVER MIX. This is the composite of
             *    BOTH books. When it does not resolve the block renders with no
             *    picture; it NEVER substitutes one component's cover, because a
             *    chapter-book cover beside this price would state that that
             *    book costs this price.
             */
            ?>
            <div class="read-aloud-combo__art">
              <?php echo $combo['art']; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_get_attachment_image() output. ?>
            </div>
          <?php endif; ?>

          <div class="read-aloud-combo__body">
            <p class="read-aloud-combo__eyebrow"><?php esc_html_e('Both books, one order', 'brave-hearts'); ?></p>

            <?php if ('' !== $combo['title']) : ?>
              <?php /* ⛔ Andrew's words, from the offer engine's own copy table — never re-typed here. */ ?>
              <h3 class="read-aloud-combo__title"><?php echo esc_html($combo['title']); ?></h3>
            <?php endif; ?>

            <?php
            /*
             * ⛔⛔ THE ENGINE'S `offer_descriptor` IS DELIBERATELY NOT PRINTED
             *     HERE, AND THE REASON WAS FOUND BY MEASURING, NOT BY TASTE.
             *
             *     On `/shop/` the card is title + descriptor and nothing else —
             *     the descriptor IS the card's body copy. This page gives the
             *     combo a real paragraph of its own, written for a parent who
             *     has just come home from the visit. Printing the descriptor as
             *     well would put THREE restatements of one fact in one block:
             *       · the title       "The Mariana Trench: book + coloring book"
             *       · the paragraph   "…the book I read from and the coloring
             *                          book that page came out of…"
             *       · the descriptor  "The chapter book and its coloring book"
             *
             * ⭐ That is the same "says the same thing twice in one tile" defect
             *    the shop card's own history records, one level up — and on a
             *    390px phone it also cost ~32px of a block already measured at
             *    792px against an 844px viewport. Removing it is both the copy
             *    call and the layout call, which is why it is removed rather
             *    than hidden with CSS.
             *
             * ⛔ `bhp_read_aloud_combo()` STILL RESOLVES AND RETURNS the
             *    descriptor. It is not deleted from the contract — a future
             *    surface on this page may want it, and re-deriving where it
             *    comes from would cost more than carrying it.
             */
            ?>
            <p class="read-aloud-combo__text">
              <?php esc_html_e('If the story is new to your house, this is the simplest way in — the book I read from and the coloring book that page came out of, added together in one go.', 'brave-hearts'); ?>
            </p>

            <?php
            /*
             * ═════════════════════════════════════════════════════════════════
             * ⭐⭐ 1.19.295 — TWO MODES. `CYCLE167-LD-001`.
             * ═════════════════════════════════════════════════════════════════
             *
             * ⭐ `cart` — the ordinary shopper. BYTE-FOR-BYTE 1.19.294: the
             *    engine's own card, its own form, its own nonce, its own ADD
             *    TO CART opening the side panel. Nothing about the normal path
             *    changed and nothing here may change it.
             *
             * ⛔⛔ `link` — a VISIT-FLAGGED session. `FD-642` forbids a
             *     colouring product in a hand-delivery cart, so this branch
             *     emits NO FORM, NO NONCE AND NO ADD-TO-CART CONTROL. It is a
             *     plain anchor. There is nothing here to press that could put
             *     a colouring book into a signed-copy order, which is why the
             *     rule is preserved BY CONSTRUCTION rather than by a check.
             *     ⛔ Do not "improve" this branch by giving it a form.
             *
             * ⭐ The price still renders, in the grid's OWN price markup, so
             *    the parent sees what the pair costs before deciding whether
             *    to give up hand-delivery for it. `R2.6` (one price, once)
             *    holds: the CTA below carries no figure.
             */
            ?>
            <?php if ('link' === ($combo['mode'] ?? 'cart')) : ?>
              <div class="read-aloud-combo__shiphome">
                <?php if ('' !== $combo['price_html']) : ?>
                  <span class="bhp-shop-from-price bhp-shop-format-prices read-aloud-combo__shiphome-price">
                    <span class="bhp-shop-format-price">
                      <?php if ('' !== $combo['price_label']) : ?>
                        <span class="bhp-shop-format-price__label"><?php echo esc_html($combo['price_label']); ?></span>
                      <?php endif; ?>
                      <span class="bhp-shop-format-price__amount"><?php echo wp_kses_post($combo['price_html']); ?></span>
                    </span>
                  </span>
                <?php endif; ?>

                <a class="read-aloud-combo__shiphome-cta" href="<?php echo esc_url($combo['url']); ?>">
                  <?php echo esc_html($combo['cta']); ?>
                </a>

                <?php /* ⛔ The honest half. Never render the control without it. */ ?>
                <p class="read-aloud-combo__shiphome-note"><?php echo esc_html($combo['note']); ?></p>
              </div>
            <?php else : ?>
              <?php echo $combo['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped at source in bhp_offer_render_module(). ?>
            <?php endif; ?>
          </div>
        </div>

        <?php
        /*
         * ⭐ THE DIVIDER EXISTS SO THE TWO TILES BELOW STILL READ AS AN OPTION
         *    RATHER THAN AS LEFTOVERS. Without it the combo looks like the
         *    answer and the pair looks like duplicate content.
         * ⛔ It is a real heading for the grid that follows, not decoration —
         *    which is why it is `<h3>` and why the grid is labelled by it.
         */
        ?>
        <h3 class="read-aloud-pair__or">
          <?php esc_html_e('Or pick just one', 'brave-hearts'); ?>
        </h3>
      <?php endif; ?>

      <?php
      /*
       * ⛔ THE GRID CARRIES NO `aria-labelledby`, DELIBERATELY. It is a plain
       *    `<div>` with no ARIA role, and `aria-labelledby` on a roleless
       *    generic element is IGNORED by assistive technology — it would look
       *    like accessibility work while doing nothing. The `<h3>` immediately
       *    above it already puts the grid under a real heading in the document
       *    outline, which is what a heading-navigating user actually uses.
       */
      ?>
      <div class="read-aloud-pair__grid">
        <?php foreach ($pair as $tile):
            $key = isset($tile['key']) ? (string) $tile['key'] : '';
            if (!isset($tile_copy[$key])) {
                continue;
            }
            $copy = $tile_copy[$key];
            $url  = bhp_get_safe_link_url($tile['url'] ?? '');
        ?>
          <article class="read-aloud-pair__card read-aloud-pair__card--<?php echo esc_attr($key); ?>">
            <div class="read-aloud-pair__art">
              <?php
              /*
               * ⛔ NO PLACEHOLDER IMAGE. `R2.3` degrade-never-mix: a card with
               *    no picture is a valid card; a card with the wrong picture
               *    is a false claim.
               */
              if (!empty($tile['image_id'])) {
                  echo wp_get_attachment_image((int) $tile['image_id'], 'woocommerce_single', false, [
                      'class'    => 'read-aloud-pair__cover',
                      'loading'  => 'eager',
                      'decoding' => 'async',
                      'alt'      => $tile['alt'] ?? '',
                      'sizes'    => '(max-width: 700px) 45vw, 300px',
                  ]);
              }
              ?>
            </div>
            <div class="read-aloud-pair__body">
              <p class="read-aloud-pair__eyebrow"><?php echo esc_html($copy['eyebrow']); ?></p>
              <h3 class="read-aloud-pair__title"><?php echo wp_kses_post($copy['title']); ?></h3>
              <p class="read-aloud-pair__text"><?php echo esc_html($copy['text']); ?></p>
              <?php if ($url): ?>
                <a class="btn btn-outline read-aloud-pair__cta" href="<?php echo esc_url($url); ?>">
                  <?php echo esc_html($copy['cta']); ?>
                </a>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php
  /*
   * ═════════════ FOR THE GROWN-UP ═════════════
   * ⛔ The voice changes here and the heading says so out loud, because the
   *    same screen is being read by two people with different questions.
   */
  ?>
  <section class="read-aloud-parent" aria-labelledby="read-aloud-parent-title">
    <div class="read-aloud__inner">
      <p class="read-aloud__eyebrow"><?php esc_html_e('For the grown-up reading this', 'brave-hearts'); ?></p>
      <h2 id="read-aloud-parent-title" class="read-aloud__section-title">
        <?php esc_html_e('A quick hello from the author', 'brave-hearts'); ?>
      </h2>

      <div class="read-aloud-parent__note">
        <p>
          <?php esc_html_e('I’m Andrew Signore. I write the Adventures of Charlotte and Henry series, and I visit elementary schools to read it out loud.', 'brave-hearts'); ?>
        </p>
        <p>
          <?php esc_html_e('I write for the child who has decided reading isn’t for them: short chapters, illustrations all the way through, and real places, real animals and real science — so it never feels like homework. Ages 6–9.', 'brave-hearts'); ?>
        </p>
        <p class="read-aloud-parent__signoff">
          <?php esc_html_e('Thanks for reading with them tonight.', 'brave-hearts'); ?>
        </p>
      </div>

      <div class="read-aloud-parent__ctas">
        <a class="btn btn-primary" href="<?php echo esc_url($shop_url); ?>">
          <?php esc_html_e('Browse every book', 'brave-hearts'); ?>
        </a>
      </div>
      <p class="read-aloud-parent__shop-note">
        <?php esc_html_e('Paperbacks, the three-book collection and the coloring book are all in the shop.', 'brave-hearts'); ?>
      </p>
    </div>
  </section>

  <?php
  /*
   * ═════════════ THE ONE CAPTURE ═════════════
   * ⛔ ONE ask on this page, and it is the EXISTING parent-funnel Adventure
   *    Kit. No new magnet, no new list, no new journey, no new endpoint.
   * ⭐ `context => read_aloud_landing` is the ONLY thing that is new, and it
   *    exists so Andrew can tell a school-visit signup from a website
   *    signup. Tag mapping: inc/read-aloud-landing.php, priority 20.
   */
  ?>
  <section id="free" class="read-aloud-capture" aria-labelledby="read-aloud-capture-title">
    <div class="read-aloud__inner">
      <?php if (!empty($download['ready'])): ?>
        <p class="read-aloud__eyebrow"><?php esc_html_e('Free for parents', 'brave-hearts'); ?></p>
        <h2 id="read-aloud-capture-title" class="read-aloud__section-title">
          <?php esc_html_e('Want the next chapter tonight?', 'brave-hearts'); ?>
        </h2>
        <p class="read-aloud-capture__lead">
          <?php esc_html_e('I’ll send you the free Reluctant Reader Adventure Kit — a sample chapter to read together, a printable explorer activity, and a few simple ways to make reading feel like an adventure again.', 'brave-hearts'); ?>
        </p>

        <?php get_template_part('template-parts/acquisition/signup-form', null, [
            'id'                   => 'read-aloud-signup-form',
            'context'              => 'read_aloud_landing',
            'audience_type'        => 'parents_families',
            'lead_magnet'          => 'reluctant_reader_adventure_kit',
            'source_page'          => $source_page,
            'success_redirect_key' => 'adventure_kit_thank_you',
            'show_name'            => true,
            'require_name'         => false,
            'name_label'           => __('Your first name', 'brave-hearts'),
            'submit_label'         => __('Send me the free chapter &amp; activity', 'brave-hearts'),
            'privacy_text'         => __('Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts'),
            'aria_labelledby'      => 'read-aloud-capture-title',
            'class'                => 'read-aloud-capture__form',
            'submit_class'         => 'btn-cta-primary',
        ]); ?>

        <p class="read-aloud-capture__fine-print">
          <?php esc_html_e('Free printable PDF · No purchase required · Unsubscribe anytime.', 'brave-hearts'); ?>
        </p>
      <?php else: ?>
        <?php /* ⛔ The kit's PDF is unset. Promise nothing rather than collect an
                  address against a resource that cannot be delivered. */ ?>
        <p class="read-aloud__eyebrow"><?php esc_html_e('Coming soon', 'brave-hearts'); ?></p>
        <h2 id="read-aloud-capture-title" class="read-aloud__section-title">
          <?php esc_html_e('The free Adventure Kit is on its way', 'brave-hearts'); ?>
        </h2>
        <p class="read-aloud-capture__lead">
          <?php esc_html_e('It’s still being finished. In the meantime, every book is in the shop.', 'brave-hearts'); ?>
        </p>
        <a class="btn btn-primary" href="<?php echo esc_url($shop_url); ?>">
          <?php esc_html_e('Browse every book', 'brave-hearts'); ?>
        </a>
      <?php endif; ?>
    </div>
  </section>

</main>

<?php get_footer(); ?>
