<?php
/**
 * Brave Hearts Publishing — Header
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="https://gmpg.org/xfn/11">
<?php
/*
 * CYCLE144-LD-201 / -202 (2026-08-05, theme 1.19.190) — THE FONTS ARE
 * SELF-HOSTED NOW, AND THE LCP IMAGE IS PRELOADED.
 *
 * ⭐ THE TWO `preconnect` HINTS BELOW ARE REMOVED, and removing them is
 *    the POINT rather than a side effect: there is no longer anything to
 *    connect to. `fonts.googleapis.com` and `fonts.gstatic.com` are no
 *    longer contacted at all, so a preconnect to either would open two
 *    TCP+TLS connections that nothing ever uses — measurably worse than
 *    nothing on a phone. The F1/F7 comment block that justified them is
 *    preserved verbatim immediately below, because its ANALYSIS is still
 *    correct and is exactly what motivated going further: it identified a
 *    four-round-trip chain and shortened it; this change deletes it.
 *
 * ⛔ THE CONSENT / GTM SEQUENCE IS UNTOUCHED. Everything emitted here
 *    lands BEFORE `wp_head()`, which is where consent-default, the
 *    WPConsent bridge and gtm.js are printed, in that order, by their own
 *    classes. Source order between them cannot change, because none of
 *    them is involved.
 *
 * Emitted here rather than on the `wp_head` hook for the same reason the
 * preconnects were: the preload scanner works in SOURCE ORDER, and a
 * preload printed after `wp_head()`'s stylesheet <link>s queues behind
 * them, which defeats the purpose.
 */
/*
 * ⭐ CYCLE144-LD-216 (2026-08-05, theme 1.19.201) — THE ORDER OF THESE THREE
 *    LINES IS THE CHANGE. The LCP preload now goes FIRST.
 *
 * SUPERSEDED ORDER, preserved so the movement is visible rather than re-derived:
 *
 *     if (function_exists('bhp_print_self_hosted_fonts')) { bhp_print_self_hosted_fonts(); }
 *     if (function_exists('bhp_print_lcp_preload'))       { bhp_print_lcp_preload(); }
 *
 * WHY IT MATTERS. `bhp_print_self_hosted_fonts()` emits THREE
 * `<link rel="preload" as="font">` tags totalling 115 KB (archivo 34.4 KB,
 * cormorant-garamond 37.0 KB, eb-garamond 43.6 KB — measured on staging).
 * Chrome assigns `as="font"` and `as="image" fetchpriority="high"` the SAME
 * High priority, so the tie is broken by SOURCE ORDER: with fonts first, the
 * image that decides LCP was queued behind 115 KB of typefaces on a 1.47 Mbps
 * link. Putting the LCP image first costs the fonts a few milliseconds of
 * queueing and buys the metric the whole 115 KB.
 *
 * ⛔ NOTHING ABOUT THE TYPEFACES CHANGES — not a family, weight, italic,
 *    `unicode-range`, `font-display` or file. The inline `@font-face` block is
 *    still printed here, still before `wp_head()`, still ahead of every
 *    stylesheet link. `font-display: swap` already governs the fallback flash
 *    that motivated self-hosting in 1.19.190, and it is unchanged.
 *
 * ⛔ THE CONSENT / GTM SEQUENCE IS STILL UNTOUCHED — none of these three
 *    functions participates in it.
 *
 * `bhp_print_art_defer_script()` joins them here rather than in the footer
 * because it must run before the stylesheet that declares the deferred
 * backgrounds is applied; by the footer the fetches have already started.
 */
if (function_exists('bhp_print_lcp_preload'))       { bhp_print_lcp_preload(); }
if (function_exists('bhp_print_self_hosted_fonts')) { bhp_print_self_hosted_fonts(); }
if (function_exists('bhp_print_art_defer_script'))  { bhp_print_art_defer_script(); }
/*
 * ⛔ SUPERSEDED 2026-08-05 — PRESERVED VERBATIM, NOT CORRECTED IN PLACE.
 *    The two <link rel="preconnect"> tags this block describes no longer
 *    exist. Everything it says about the chain remains true of the state
 *    it was written in.
 *
 * F1/F7 (2026-08-03) — FONT CONNECTION HINTS.
 *
 * Measured before this change (CAPSTONE-AUDIT-TECHNICAL §1.4): the page
 * emitted `preconnect` count ZERO — including nothing at all for
 * `fonts.gstatic.com`, which is a SECOND, entirely separate origin from
 * `fonts.googleapis.com` and is where all six font FILES actually come
 * from. The critical chain was HTML -> render-blocking CSS on host A
 * (new DNS + TLS + request) -> parse -> six requests to host B (another
 * new DNS + TLS). At the measured 85 ms RTT on 4G that is roughly four
 * extra round trips before a branded glyph paints; the observed fallback
 * flash was 744 ms on 4G and materially longer on Fast 3G.
 *
 * `crossorigin` is REQUIRED on the gstatic hint — font files are fetched
 * in CORS mode, and a preconnect without it opens a connection the font
 * request cannot reuse, which is a silent no-op rather than an error.
 *
 * Emitted here rather than through `wp_resource_hints` so both hints land
 * ahead of `wp_head()`'s stylesheet links in source order, which is what
 * lets the preload scanner act on them.
 *
 *   <link rel="preconnect" href="https://fonts.googleapis.com">
 *   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
 *
 * END OF SUPERSEDED BLOCK.
 */
?>
<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e('Skip to content', 'brave-hearts'); ?></a>

<div class="site-wrapper">

<header class="site-header" role="banner">
  <div class="header-inner">
    <div class="site-logo">
      <?php
      /*
       * Wave F (2026-08-03) -- the REVERSED lockup, served from the theme.
       *
       * This deliberately no longer calls the_custom_logo() here. Three
       * measured reasons, none of them preference:
       *
       * 1. The Customizer logo (staging attachment 760) is the NAVY-on-cream
       *    horizontal lockup. FD-32 had to sit it on a cream PLATE to make it
       *    legible on this near-black header -- the plate Andrew asked to be
       *    removed. A reversed export now exists, so the plate is unnecessary.
       * 2. `custom_logo` is a per-site theme_mod, and PRODUCTION HAS NONE
       *    (verified live 2026-08-02: the staging theme_mod carries
       *    custom_logo 760, production's carries no custom_logo key at all and
       *    renders the text wordmark). A Customizer-based fix would therefore
       *    NOT travel with the theme deploy, and the two environments would
       *    keep diverging. A theme asset ships with the ZIP and lands on both.
       * 3. The five audience landing pages call the_custom_logo() on their own
       *    LIGHT backgrounds, where the navy lockup is correct. They are
       *    untouched -- which is exactly why this change is made here rather
       *    than by swapping the Customizer attachment.
       *
       * The asset is `assets/images/brand/brave-hearts-horizontal-reversed-rose.png`
       * -- cream + gold on TRUE transparency, no plate, no background. See the
       * release record for how it was derived from the approved export.
       */
      /*
       * F15 (2026-08-03) — THE REVERSED **ROSE** LOCKUP, RE-ENCODED BEFORE
       * IT SHIPPED.
       *
       * The approved export
       * `WORKING-DRAFTS\design-creative\REVERSED-ROSE-horizontal-v1-TRIM@2x.png`
       * is 1280x400 RGBA, 53,162 bytes. Dropping it in as-is would have cost
       * +36 KB on EVERY page load for a mark rendered 30-50 px tall — a
       * 400 px-tall source for a 38 px box is more than 10x oversupply, and
       * the "1x" file in that set is LARGER than the "2x" one (57.6 KB vs
       * 51.9 KB), which is the signature of an unoptimised export rather than
       * a considered pair.
       *
       * What was done, and it is the ONLY thing done to the artwork:
       *   1. crop the fully-transparent margin  (1280x400 -> 1250x370)
       *   2. Lanczos downscale to 338x100, i.e. 2x the LARGEST rendered box
       *      on the site (the footer's 153x50)
       *   3. encode as a palette PNG with alpha, 128 colours
       *
       * ⛔ NO PIXEL OF THE MARK WAS REDRAWN, RECOLOURED, RE-TRACED OR
       *    REGENERATED. No glyph, no rose point and no letterform was touched.
       *    This is a resample and a re-encode of the approved file — measured
       *    mean absolute error against a straight Lanczos resize of the same
       *    source: 0.81/255 per channel, max channel delta 28, i.e. visually
       *    identical and quantifiably so.
       *
       * RESULT: 6,090 bytes, against the 16,694 bytes of the file it replaces
       * and the 53,162 bytes of shipping the export raw. The swap REDUCES page
       * weight by 10.6 KB while raising the effective resolution of the mark.
       *
       * The previous asset `brave-hearts-horizontal-reversed.png` is NOT
       * deleted. Rollback is changing this one filename back.
       */
      ?>
      <a class="site-logo__lockup" href="<?php echo esc_url(home_url('/')); ?>">
        <img class="site-logo__mark"
             src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/brand/brave-hearts-horizontal-reversed-rose.png'); ?>"
             alt="<?php echo esc_attr(get_bloginfo('name')); ?>"
             width="338" height="100" decoding="async">
      </a>
    </div>

    <?php
    /*
     * ⭐ 1.19.260 (2026-08-19) — THE MOBILE-HEADER OFFER. `CYCLE165-LD-DIRECTION1-STEP1-HEADER`.
     *
     * ONE compact primary offer, OUTSIDE the hamburger, at the mobile nav
     * breakpoint. It exists because `.header-expedition-cta` immediately below
     * is `display: none` there and `.site-nav__cta` is inside the CLOSED
     * dropdown, so a phone visitor had to open a menu before this site offered
     * to sell anything. `commerce-cx` measured 69 of 83 pages with no buy CTA
     * above the fold at 390.
     *
     * ⛔ IT IS PLACED BEFORE `.nav-toggle` DELIBERATELY, AND THE ORDER IS
     *    LOAD-BEARING. `.header-inner` is `justify-content: space-between`, so
     *    the toggle must stay the rightmost item — the 2026-07-14 P0 in
     *    style.css is a toggle pushed off the right edge of a phone, invisible
     *    rather than merely broken. Putting the offer to its LEFT keeps the
     *    toggle's position and the logo's box unchanged.
     *
     * ⛔ IT RENDERS NOTHING AT DESKTOP/TABLET, on any page. The whole component
     *    is `display: none` outside the same `@container (max-width: 1116px)`
     *    query that hides `.header-expedition-cta` — the same container, the
     *    same breakpoint, so the two can never both show or both hide. The
     *    desktop header is byte-identical to 1.19.259.
     *
     * The `function_exists()` guard is the same premium `.header-expedition-cta`
     * pays three lines below, for the same reason: this is the SITEWIDE header
     * and a fatal here is a whole-site outage. There is deliberately NO inline
     * fallback — an offer button whose renderer is missing has no live price to
     * print, and a hardcoded price on a sitewide purchase control is exactly
     * what `inc/header-offer.php` refuses to ship.
     */
    if ( function_exists( 'bhp_header_offer' ) ) {
      echo bhp_header_offer(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes every component
    }
    ?>

    <button class="nav-toggle" aria-label="<?php esc_attr_e('Toggle navigation', 'brave-hearts'); ?>" aria-controls="primary-navigation" aria-expanded="false">&#9776;</button>

    <nav class="site-nav" id="primary-navigation" role="navigation" aria-label="<?php esc_attr_e('Primary navigation', 'brave-hearts'); ?>">
      <?php
      wp_nav_menu([
        'theme_location' => 'primary',
        'menu_class'     => '',
        'container'      => false,
        'fallback_cb'    => 'bhp_fallback_menu',
      ]);
      ?>
      <?php
      /*
       * 1.19.192 (2026-08-05) — Andrew Signore's current-turn ruling, verbatim:
       * "the nav bar get the complete collection should go to the collection
       * page not the checkout". RELAYED through the Chief of Staff; NOT
       * witnessed by the agent that made this change.
       *
       * ⭐ THIS REVERSES HIS OWN SAME-DAY ITEM 8 ("Convert to hardcover
       *    purchase", shipped as 1.19.183) — knowingly, after using the shipped
       *    flow himself. Both header controls are plain anchors to
       *    /complete-collection/ again, unconditionally. That page's own format
       *    toggle and two-click checkout CTAs are the purchase path and are
       *    UNCHANGED, as are the funnel pages, the homepage band and /books/.
       *
       * The whole decision — the measured 53.27px nav-link shift and the 2.5s
       * "Adding to cart…" dwell that prompted it, why the suppression guard is
       * subsumed rather than lost, and why `aria-current` comes back — is
       * documented ONCE, in inc/collection-cta.php. It is not restated here so
       * the two cannot drift apart.
       *
       * ⚠ THIS IS THE SITEWIDE HEADER: it renders on every page. The
       *   `function_exists()` guard stays — a header that fatals is a
       *   whole-site outage, and three lines is a cheap premium. The inline
       *   fallback below is now identical to what the renderer returns, which
       *   is exactly the property that makes the guard free.
       */
      if ( function_exists( 'bhp_collection_header_cta' ) ) {
        echo bhp_collection_header_cta( array( 'variant' => 'nav' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes every component
      } else {
        ?>
        <a class="site-nav__cta" href="<?php echo esc_url(home_url('/complete-collection/')); ?>"<?php echo is_page('complete-collection') ? ' aria-current="page"' : ''; ?>>
          <?php esc_html_e('Get the Complete Collection', 'brave-hearts'); ?>
        </a>
        <?php
      }
      ?>
    </nav>

    <?php
    if ( function_exists( 'bhp_collection_header_cta' ) ) {
      echo bhp_collection_header_cta( array( 'variant' => 'bar' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes every component
    } else {
      ?>
      <a class="header-expedition-cta" href="<?php echo esc_url(home_url('/complete-collection/')); ?>"<?php echo is_page('complete-collection') ? ' aria-current="page"' : ''; ?>>
        <?php esc_html_e('Get the Complete Collection', 'brave-hearts'); ?>
      </a>
      <?php
    }
    ?>
  </div>
</header>

<main class="site-main" id="main">
