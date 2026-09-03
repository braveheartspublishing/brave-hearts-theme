<?php
/**
 * Brave Hearts Publishing — Standard Page Template
 */
get_header();

while (have_posts()): the_post();
  $slug = get_post_field('post_name', get_post());

  /*
   * ═══════════════════════════════════════════════════════════════════════
   * ⭐ 1.19.194 (2026-08-05) — THE CHECKOUT PAGE LOSES ITS HERO. CYCLE144-LD-210.
   * ═══════════════════════════════════════════════════════════════════════
   *
   * Andrew Signore, 2026-08-05, verbatim (⛔ RELAYED through the Chief of
   * Staff; NOT witnessed first-hand by the agent that wrote this):
   *
   *   "Remove the whole section on the checkout page "Brave Hearts Field
   *    Journal Checkout" - its clearly understood that its a check out
   *    page- bring everything up"
   *
   * The section is this template's `interior-hero`: the "Brave Hearts Field
   * Journal" eyebrow, the page's own <h1> ("Checkout") and the "FIELD NOTE ·
   * BHP" coordinate. On the checkout page it is three lines of decoration
   * above the form that takes the money, and removing it raises the form.
   *
   * ⛔ SUPPRESSED ON THE CHECKOUT PAGE ONLY, NOT DELETED, and not by
   *    creating page-checkout.php. Both alternatives were rejected for
   *    reasons that are already recorded in the codebase:
   *
   *      · Deleting the hero would strip the eyebrow and <h1> from every
   *        other page that uses this template.
   *      · A dedicated `page-checkout.php` would take the checkout page out
   *        of this template entirely — and with it the
   *        `.page-content.page-checkout` wrapper that
   *        assets/css/checkout-experience.css's 1240px desktop rule targets.
   *        That rule is the whole of the 1.19.185 two-column desktop
   *        checkout fix, and losing it has NO OTHER SYMPTOM than the
   *        duplicate order summary silently returning.
   *        tests/test-checkout-desktop-layout.php §5d/§5e assert exactly
   *        that, and they still pass because of this decision.
   *
   * ⛔ THE ORDER-RECEIVED ENDPOINT IS NOT AFFECTED. `is_checkout()` is true
   *    on the thank-you page too, but the thank-you page is
   *    /checkout/order-received/ and still runs through this template with
   *    its own title. It is excluded so a customer who has just paid still
   *    gets a heading on the page confirming it.
   *
   * ⛔ NO OTHER PAGE CHANGES. The condition is the WooCommerce checkout page
   *    id, read from WooCommerce itself (`wc_get_page_id('checkout')`),
   *    never a hardcoded id and never a slug string.
   */
  $bhp_is_checkout_page = false;
  if (function_exists('is_checkout') && function_exists('is_order_received_page')) {
      $bhp_is_checkout_page = is_checkout() && !is_order_received_page();
  }
?>

<?php if (!$bhp_is_checkout_page): ?>
<header class="interior-hero interior-hero--parchment">
  <div class="container container--content">
    <?php /* ⭐ 1.19.269 item 5 (founder ruling, 2026-08-19) — REMOVED, decoration above the page H1:
            <p class="component-heading__eyebrow"><?php esc_html_e('Brave Hearts Field Journal', 'brave-hearts'); ?></p>
       The keep/remove test is stated once, in full, in `page-about.php`. */ ?>
    <h1><?php the_title(); ?></h1>
    <?php
    /*
     * ⭐ 1.19.355 (`CYCLE179-LD-355`, brief item 4) — `D8`: the decorative
     *    coordinate is suppressed on utility and legal pages.
     *
     * ⛔ SUPERSEDED LINE, PRESERVED SO THE MOVEMENT IS VISIBLE AND IS NOT
     *    RE-DERIVED:
     *
     *        <span class="interior-hero__coordinate" aria-hidden="true">FIELD NOTE · BHP</span>
     *
     * ⭐ THE WORDS ARE UNCHANGED and the element is unchanged. Only the
     *    question of WHICH pages print it has moved, and the answer lives in
     *    `bhp_page_hero_shows_coordinate()` in `inc/page-hero.php` where the
     *    option lookups and the one slug fallback are documented. Every other
     *    page using this template prints it exactly as it did in 1.19.354.
     */
    if (!function_exists('bhp_page_hero_shows_coordinate') || bhp_page_hero_shows_coordinate(get_the_ID())) :
    ?>
    <span class="interior-hero__coordinate" aria-hidden="true">FIELD NOTE · BHP</span>
    <?php endif; ?>
  </div>
</header>
<?php else: ?>
<?php
/*
 * ⚠ ONE THING SURVIVES THE REMOVAL, AND IT IS FLAGGED RATHER THAN
 *   ASSUMED APPROVED: a visually-hidden <h1>.
 *
 * Andrew's instruction is about what he can SEE — "its clearly understood
 * that its a check out page". Nothing below is visible: `.screen-reader-text`
 * (style.css:199) is the standard WordPress clip-rect utility, it occupies no
 * space, and "bring everything up" is fully satisfied — the Blocks checkout
 * now starts at the top of the page.
 *
 * What it prevents is a page with NO <h1> at all, which is a document-outline
 * and assistive-technology regression rather than a design one, on the single
 * page a customer is least able to abandon. If Andrew wants it gone too, it is
 * a two-line deletion.
 */
?>
<h1 class="screen-reader-text"><?php the_title(); ?></h1>
<?php endif; ?>
<div class="page-content page-<?php echo esc_attr($slug); ?>">
  <article id="post-<?php the_ID(); ?>" <?php post_class('entry-content flow editorial-surface'); ?>>
    <?php
    /*
     * ⭐⭐ 1.19.355 (`CYCLE179-LD-355`, brief item 4) — `D1`: ONE <h1> PER PAGE.
     *
     * ⛔ SUPERSEDED LINE, PRESERVED SO THE MOVEMENT IS VISIBLE AND IS NOT
     *    RE-DERIVED: this template's only content statement was a bare
     *    `the_content()` call, inside its own PHP tags, on one line.
     *    (The literal is described rather than quoted because a PHP close tag
     *    inside a comment is exactly the kind of thing a later editor moves
     *    into a `//` comment, where it WOULD end PHP mode mid-template.)
     *
     * ⭐ WHAT THIS IS: `the_content()`'s own two steps, written out, with one
     *    call between them. `the_content()` is `apply_filters('the_content',
     *    get_the_content())` followed by the `]]>` guard, and both are
     *    reproduced verbatim below so that filtering, shortcodes, blocks,
     *    oEmbeds and `wpautop` all run exactly as before. VERIFIED BY READING
     *    WordPress core's `wp-includes/post-template.php`, not assumed.
     *
     * ⛔ WHY NOT A `the_content` FILTER, AND IT IS THE REASON THIS REPOSITORY
     *    ALREADY RECORDS. `CLAUDE.md` and `docs/DECISIONS.md` both say the
     *    removed Teachers-page `the_content` filter must not be reintroduced. A
     *    global filter would also run for the REST API, feeds, search excerpts
     *    and every other template, where the hero this deduplicates against
     *    does not exist. This runs on this template and nowhere else.
     *
     * ⛔ IT REMOVES A HEADING FROM THE OUTPUT, NEVER FROM THE DATABASE. The
     *    stored article is untouched; the brief lists content edits as out of
     *    scope and Andrew's.
     *
     * MEASURED BEFORE, on staging 1.19.354 at an asserted 1440: `/read-alouds/`
     * `h1_count = 2`, the second at y518 repeating the hero's word for word.
     */
    $bhp_page_html = apply_filters('the_content', get_the_content(null, false, get_post()));
    $bhp_page_html = str_replace(']]>', ']]&gt;', $bhp_page_html);

    if (function_exists('bhp_page_drop_duplicate_h1')) {
        $bhp_page_html = bhp_page_drop_duplicate_h1($bhp_page_html, get_the_title());
    }

    echo $bhp_page_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already through the_content filters, exactly as the_content() emits it.
    ?>
  </article>
</div>

<?php endwhile; ?>

<?php get_footer(); ?>
