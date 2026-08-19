<?php
/**
 * Homepage hero component.
 *
 * @param array $args {
 *   @type string $id             Optional section ID.
 *   @type string $eyebrow        Short brand or series label.
 *   @type string $title          Required hero heading.
 *   @type string $text           Supporting copy; safe inline HTML allowed.
 *   @type array  $primary_link   URL and label for the book-first action.
 *   @type array  $secondary_link URL and label for the adventure action.
 *   @type int    $image_id       Decorative cinematic image attachment ID.
 *   @type string $class          Additional section class.
 *   @type string $aside          Optional supporting visual HTML.
 *   @type bool   $aside_after_title When true, $aside is rendered immediately
 *                                     after the <h1> instead of at the end of
 *                                     the hero content. Defaults to false, so
 *                                     every existing caller keeps its current
 *                                     DOM order untouched. The aside markup is
 *                                     rendered exactly ONCE either way -- this
 *                                     moves the single node, it does not
 *                                     duplicate it or hide a second copy.
 *   @type string $details        Optional supporting detail row HTML.
 *   @type string $commercial_subtext Optional one-line plain-text commercial
 *                                     explanation shown near the CTAs, distinct
 *                                     from the brand-voice $text above it.
 *   @type string $lead           Optional HTML rendered as the FIRST child of
 *                                     the hero content, above $eyebrow.
 *   @type string $title_emphasis Optional single word inside $title to wrap in
 *                                     `<em class="home-hero__title-mark">`.
 *   @type string $after_title    Optional HTML rendered immediately after the
 *                                     H1 and BEFORE $aside. Added 1.19.251 so
 *                                     the homepage can put its primary
 *                                     invitation above the three-book fan on a
 *                                     phone while the ghost invitation stays in
 *                                     $after_text. Defaults to '' -- the six
 *                                     non-homepage callers emit nothing and
 *                                     their DOM is byte-identical.
 *   @type string $after_text     Optional HTML rendered immediately after
 *                                     $text and before $commercial_subtext.
 * }
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.241 (2026-08-18) — CYCLE164-LD-HOMEPAGE-WARMTH. THREE SLOTS ADDED.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The Homepage Warmth Board (Legolas, `FD-391`, 2026-08-17) puts a founder
 * chip above the eyebrow, a drawn underline under one word of the H1, and two
 * invitations under the subcopy. All three are HOMEPAGE-ONLY.
 *
 * ⛔ A FOURTH SLOT (`foot`, for the board's hero trust strip) WAS BUILT AND
 *    THEN REMOVED BEFORE COMMIT, because the board's own sheet 1 says of the
 *    trust anchors: "The trust anchors stay exactly where they are." They do.
 *    See the block at the hero call site in `front-page.php` for the full
 *    reasoning, including why "30-Day Guarantee" is not added to this page.
 *
 * ⛔ WHY SLOTS RATHER THAN MARKUP. This component also renders /about/,
 *    /books/, /contact/, /teachers/, /explorer-passport/ and every campaign
 *    landing page. `lead`, `title_emphasis` and `after_text` all
 *    default to '', so the rendered output for all six other callers is
 *    BYTE-IDENTICAL to 1.19.240. Diff them to confirm — none of them passes
 *    any of the three.
 *
 * ⛔ WHY `after_text` RATHER THAN REUSING `primary_link` / `secondary_link`
 *    FOR THE HOMEPAGE'S TWO NEW CTAs. Those two arguments render bare
 *    `<a class="btn">` with no attribute hook, and the homepage's CTAs must
 *    carry `data-bhp-event` / `data-bhp-source` so the existing delegated
 *    handler in `assets/js/nav.js` (line 78, `[data-bhp-event]`) tracks them
 *    like every other tracked control on the site. Widening the link
 *    arguments to accept arbitrary attributes would have changed the
 *    contract for all six other callers; a slot changes it for none.
 *    ⭐ `primary_link` / `secondary_link` ARE NOT REMOVED and are not altered.
 *
 * ⛔ `title_emphasis` DOES NOT ACCEPT HTML. Every segment of the title is
 *    still passed through `esc_html()` individually; the only markup this
 *    component introduces is the literal `<em>` wrapper it writes itself.
 *    If the word is not found in the title, the title renders exactly as
 *    before — it fails to the plain heading, never to a broken one.
 *
 * Homepage mobile reading order (2026-07-31): the homepage passes
 * `aside_after_title` so the three-book preview sits directly beneath the H1,
 * giving a visitor the product before any further copy. This is a real DOM
 * move, not a CSS reorder -- `order`, absolute positioning and transforms are
 * deliberately NOT used to fake it, so the keyboard/tab order on a phone
 * matches what is on screen. Desktop and tablet are unaffected visually
 * because the preview is absolutely positioned at those widths and is
 * therefore out of flow regardless of where it sits among its siblings.
 */
defined('ABSPATH') || exit;

$args = wp_parse_args($args ?? [], [
    'id'                 => '',
    'eyebrow'            => '',
    'title'              => '',
    'text'               => '',
    'primary_link'       => [],
    'secondary_link'     => [],
    'image_id'           => 0,
    'class'              => '',
    'aside'              => '',
    'aside_after_title'  => false,
    'details'            => '',
    'commercial_subtext' => '',
    'lead'               => '',
    'title_emphasis'     => '',
    'after_title'        => '',
    'after_text'         => '',
]);

if (!$args['title']) {
    return;
}

/*
 * The H1, with at most ONE word wrapped for the drawn underline.
 *
 * Escaping note, because this is the one place the component emits markup it
 * was not handed: the title is split on the emphasis word and EACH of the
 * three pieces is escaped independently. Nothing from $args reaches the
 * output unescaped. `strpos` is used rather than a regex so a word containing
 * regex metacharacters cannot change the match, and only the FIRST occurrence
 * is wrapped -- the board's rule is "one word per headline, never two".
 */
$title_html = esc_html($args['title']);
if ('' !== $args['title_emphasis']) {
    $mark_pos = strpos($args['title'], $args['title_emphasis']);
    if (false !== $mark_pos) {
        $mark_len   = strlen($args['title_emphasis']);
        $title_html = esc_html(substr($args['title'], 0, $mark_pos))
            . '<em class="home-hero__title-mark">' . esc_html($args['title_emphasis']) . '</em>'
            . esc_html(substr($args['title'], $mark_pos + $mark_len));
    }
}

// Only meaningful when there is actually an aside to move.
$aside_after_title = !empty($args['aside_after_title']) && $args['aside'] !== '';

$section_id = $args['id'] ?: wp_unique_id('home-hero-');
$heading_id = $section_id . '-title';
$classes    = trim(
    'home-hero section--dark '
    . ($aside_after_title ? 'home-hero--aside-after-title ' : '')
    . sanitize_html_class($args['class'])
);
$primary    = wp_parse_args($args['primary_link'], ['url' => '', 'label' => '']);
$secondary  = wp_parse_args($args['secondary_link'], ['url' => '', 'label' => '']);
$primary['url'] = bhp_get_safe_link_url($primary['url']);
$secondary['url'] = bhp_get_safe_link_url($secondary['url']);
?>
<section id="<?php echo esc_attr($section_id); ?>" class="<?php echo esc_attr($classes); ?>" aria-labelledby="<?php echo esc_attr($heading_id); ?>">
  <?php if ($args['image_id']): ?>
    <div class="home-hero__media" aria-hidden="true">
      <?php echo wp_get_attachment_image((int) $args['image_id'], 'full', false, [
          'class'         => 'home-hero__image',
          'alt'           => '',
          'loading'       => 'eager',
          'fetchpriority' => 'high',
      ]); ?>
    </div>
  <?php endif; ?>
  <div class="home-hero__overlay" aria-hidden="true"></div>
  <div class="container home-hero__content">
    <?php if ($args['lead']): ?><?php echo wp_kses_post($args['lead']); ?><?php endif; ?>
    <?php if ($args['eyebrow']): ?><p class="home-hero__eyebrow"><?php echo esc_html($args['eyebrow']); ?></p><?php endif; ?>
    <h1 id="<?php echo esc_attr($heading_id); ?>" class="text-hero home-hero__title"><?php echo wp_kses_post($title_html); ?></h1>
    <?php /* 1.19.251: $after_title lands between the H1 and the aside so the
             homepage's primary invitation precedes the three-book fan IN THE
             DOM. Every other caller passes '' and emits nothing here. */ ?>
    <?php if ($args['after_title']): ?><?php echo wp_kses_post($args['after_title']); ?><?php endif; ?>
    <?php /* The aside renders here OR at the end of this container -- never
             both. See the $aside_after_title guard on the closing block. */ ?>
    <?php if ($aside_after_title): ?><?php echo wp_kses_post($args['aside']); ?><?php endif; ?>
    <?php if ($args['text']): ?><div class="text-lead home-hero__text"><?php echo wp_kses_post($args['text']); ?></div><?php endif; ?>
    <?php if ($args['after_text']): ?><?php echo wp_kses_post($args['after_text']); ?><?php endif; ?>
    <?php if ($args['commercial_subtext']): ?><p class="home-hero__commercial-subtext"><?php echo esc_html($args['commercial_subtext']); ?></p><?php endif; ?>
    <?php if (($primary['url'] && $primary['label']) || ($secondary['url'] && $secondary['label'])): ?>
      <div class="home-hero__actions cluster">
        <?php if ($primary['url'] && $primary['label']): ?><a class="btn btn-primary" href="<?php echo esc_url($primary['url']); ?>"><?php echo esc_html($primary['label']); ?></a><?php endif; ?>
        <?php if ($secondary['url'] && $secondary['label']): ?><a class="btn btn-outline" href="<?php echo esc_url($secondary['url']); ?>"><?php echo esc_html($secondary['label']); ?></a><?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if ($args['details']): ?><div class="home-hero__details"><?php echo wp_kses_post($args['details']); ?></div><?php endif; ?>
    <?php if ($args['aside'] && !$aside_after_title): ?><?php echo wp_kses_post($args['aside']); ?><?php endif; ?>
  </div>
</section>
