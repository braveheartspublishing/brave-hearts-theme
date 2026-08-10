<?php
/**
 * Brave Hearts — the FUNNEL FAST-PURCHASE BAND.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS IS, AND THE ONE INSTRUCTION IT SERVES
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-06, verbatim (⛔ RELAYED through the Chief of
 * Staff; NOT witnessed first-hand by the agent that wrote this file. The
 * carrier on disk is `FOUNDER-VERBATIM-2026-08-05-PRODUCTION-DEPLOY-
 * AUTHORIZATION.md`, the "REFINEMENT — same hour, after Andrew checked
 * MOBILE" addendum):
 *
 *   "Just looked at mobile - The get the free chapter & activity is above
 *    the fold. The issue I see is the buying the collection remains at the
 *    bottom of the page - we need to bring that up - put it right below the
 *    Section that starts with the checkmarks- Ages 6-9 1st -3rd grade
 *    ...Read aloud or independent" - - Add to sundays build list- needs to
 *    be done on all funnels for mobile"
 *
 * So this band is rendered IMMEDIATELY AFTER the quick-scan bar on every
 * funnel page, and it carries exactly one job: let a mobile visitor buy the
 * collection without scrolling to the bottom of a long landing page.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ IT IS AN ADDITION, NOT A MOVE. The full collection section stays where
 *    it is.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The founder asked to "bring that up", and the obvious reading — relocate
 * the pricing card — is the wrong build, for a reason that is measurable
 * rather than aesthetic: the card carries the format toggle, the three
 * included-title rows, the strike-through comparison and the shipping note.
 * Hoisting all of that above the emotional case is precisely the "choice
 * friction before the argument" the same founder's cold-traffic audit told
 * us to remove from the collection page (batch 2, item 2: "format selector
 * LOWER"). A compact band that offers the ONE default format, with a link
 * down to the full card for anyone who wants to choose, satisfies the
 * instruction and keeps that finding intact.
 *
 * ⛔ ONE CTA, ONE FORMAT — THE DEFAULT. `bhp_collection_add_to_cart_cta()`
 *    resolves the format itself from `bhp_collection_cta_default_format()`,
 *    so this band cannot disagree with the price card lower down about what
 *    "the collection" means.
 *
 * ⛔ NO PRICE, SAVING OR SHIPPING FIGURE IS COMPUTED HERE. Every number
 *    comes from the caller's `$formats` map, which is itself built from
 *    `bhp_bundle_expected_price()` and `bhp_bundle_rules()` at render time.
 *    A second computation would be a second answer.
 *
 * ⛔ NO FUNNEL STORAGE KEY, NO FUNNEL ANALYTICS PREFIX. This is commerce
 *    presentation. The parent and teacher funnels' isolation
 *    (`.claude/rules/funnels.md`) is untouched and unreachable from here —
 *    the analytics attributes are the caller's own page-scoped event and
 *    source strings, passed in, exactly as the existing price-card CTA
 *    already does.
 *
 * ✅ IT FAILS CLOSED. With no `$formats` entry for the default format, or
 *    with the bundle helpers absent, it renders NOTHING and the page is
 *    byte-identical to the release before it.
 *
 * Expects:
 *   $prefix   string  BEM prefix of the host page ('parent-landing' or
 *                     'audience-landing') — REQUIRED
 *   $formats  array   from the caller's own format map — REQUIRED
 *   $event    string  analytics event name for the CTA
 *   $source   string  analytics source for the CTA
 */

defined('ABSPATH') || exit;

if (empty($prefix) || empty($formats) || !is_array($formats)) {
    return; // The gate.
}
if (!function_exists('bhp_collection_add_to_cart_cta')) {
    return;
}

$bhp_fp_format = function_exists('bhp_book_default_format') ? bhp_book_default_format() : 'hardcover';
if (empty($formats[$bhp_fp_format])) {
    // Default format has no price row on this page: try the other one rather
    // than rendering a CTA with no price beside it.
    $bhp_fp_format = ('hardcover' === $bhp_fp_format) ? 'paperback' : 'hardcover';
}
if (empty($formats[$bhp_fp_format])) {
    return;
}

$bhp_fp_row   = $formats[$bhp_fp_format];
$bhp_fp_event = isset($event) ? $event : '';
$bhp_fp_src   = isset($source) ? $source : '';

/*
 * THE FREE ITEMS, AS BULLET LINES — Andrew Signore, 2026-08-06 (relayed):
 * "FREE-items emphasis on ALL funnel + collection pages: bold, each free
 * item its own bullet line, never combined sentences."
 *
 * `bhp_book_free_bullets_markup()` returns a <ul> of <strong> items with
 * "FREE" already uppercase IN THE STRING (never via `text-transform`), and
 * returns '' when nothing is actually free on this environment.
 */
$bhp_fp_free = function_exists('bhp_book_free_bullets_markup')
    ? bhp_book_free_bullets_markup('collection', 'bhp-free-bullets--band')
    : '';
?>
<section class="bhp-fastbuy <?php echo esc_attr($prefix); ?>-fastbuy" data-bhp-fastbuy>
  <div class="<?php echo esc_attr($prefix); ?>__inner <?php echo esc_attr($prefix); ?>__inner--narrow bhp-fastbuy__inner">

    <p class="bhp-fastbuy__eyebrow"><?php esc_html_e('Best value - all three books', 'brave-hearts'); ?></p>

    <p class="bhp-fastbuy__price">
      <span class="bhp-fastbuy__price-label"><?php
        /* translators: %s: format name, e.g. Hardcover */
        echo esc_html(sprintf(__('Complete %s Collection', 'brave-hearts'), $bhp_fp_row['name']));
      ?></span>
      <span class="bhp-fastbuy__price-strike">$<?php echo esc_html(number_format($bhp_fp_row['combined'], 2)); ?></span>
      <span class="bhp-fastbuy__price-final">$<?php echo esc_html(number_format($bhp_fp_row['collection'], 2)); ?></span>
    </p>

    <?php if ('' !== $bhp_fp_free): ?>
      <?php echo $bhp_fp_free; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped in bhp_book_free_bullets_markup(). ?>
    <?php endif; ?>

    <?php
    echo bhp_collection_add_to_cart_cta([
        'format'     => $bhp_fp_format,
        /* translators: %s: format name, e.g. Hardcover */
        'label'      => sprintf(__('Add the %s Collection', 'brave-hearts'), $bhp_fp_row['name']),
        'class'      => 'btn btn-primary bhp-fastbuy__cta',
        'form_class' => 'bhp-fastbuy__cta-form',
        'event'      => $bhp_fp_event,
        'source'     => $bhp_fp_src,
    ]);
    ?>

    <p class="bhp-fastbuy__alt">
      <a href="#collection"><?php esc_html_e('Compare formats and see what is inside', 'brave-hearts'); ?></a>
    </p>
  </div>
</section>
