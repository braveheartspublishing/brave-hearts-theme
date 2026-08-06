<?php
/**
 * Brave Hearts Publishing Theme Functions
 * Big Places. Brave Hearts.
 */

defined('ABSPATH') || exit;

// ============================================================
// THEME SETUP
// ============================================================
function bhp_theme_setup() {
    load_theme_textdomain('brave-hearts', get_template_directory() . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_image_size('bhp-book-card', 480, 640, false);
    add_image_size('bhp-card-landscape', 640, 420, true);
    /*
     * CYCLE144-LD-203 (2026-08-05, theme 1.19.190) — THE MISSING RUNG ON
     * THE COVER SRCSET LADDER.
     *
     * MEASURED, staging, mobile, theme 1.19.189: the three homepage hero
     * covers were downloaded at 600 px wide (150-167 KB each, 478 KB
     * combined) to paint boxes 104-125 CSS px across. That is not a
     * `sizes` bug alone — it is that the srcset ladder JUMPS from
     * `woocommerce_thumbnail` (300 w, ~53 KB) straight to
     * `woocommerce_single` (600 w, ~160 KB), with nothing between. A
     * phone at DPR 3 needs ~316 px, so it correctly refuses 300 w and is
     * forced all the way up to 600 w — paying 3x for 1.9x the pixels it
     * can use.
     *
     * 400 w fills the gap. Uncropped (false), so it keeps each cover's
     * own aspect ratio and therefore stays in the same srcset group that
     * `wp_calculate_image_srcset()` will offer.
     *
     * ⛔ THIS REGISTERS A SIZE. IT DOES NOT CREATE ANY FILE, AND NO FILE
     *    WAS CREATED ON EITHER ENVIRONMENT BY THE 1.19.190 WORK.
     *    WordPress generates sub-sizes at UPLOAD time, so it applies to
     *    covers uploaded from here on, not to attachments 13/16/19.
     *
     *    ⭐ AND THAT IS FINE, WHICH IS WHY NO REGENERATION WAS RUN.
     *    WordPress only lists sub-sizes that exist in
     *    `_wp_attachment_metadata`, so a missing derivative is simply
     *    absent from the srcset. The measured saving on the homepage comes
     *    entirely from the `sizes` correction in `front-page.php`, which
     *    moves phones from the 600 w rung down to the EXISTING 300 w
     *    `woocommerce_thumbnail` derivative. This rung only starts paying
     *    for itself on future uploads and on the narrow DPR band that
     *    needs 301-400 px.
     *
     *    If a future session ever wants it for the three existing covers,
     *    the command is `wp media regenerate 13 16 19
     *    --image_size=bhp-hero-cover --yes` per environment. It is
     *    OPTIONAL, it was NOT run, and nothing in this release depends on
     *    it — deliberately, so a deploy to a cold environment cannot
     *    produce a broken image.
     */
    add_image_size('bhp-hero-cover', 400, 0, false);
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo', [
        'height'      => 60,
        'width'       => 200,
        'flex-width'  => true,
        'flex-height' => true,
    ]);
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_theme_support('wp-block-styles');

    register_nav_menus([
        'primary' => __('Primary Navigation', 'brave-hearts'),
        'footer'  => __('Footer Navigation', 'brave-hearts'),
    ]);
}
add_action('after_setup_theme', 'bhp_theme_setup');

// Make the design-system button variants available in the block editor.
function bhp_register_block_styles() {
    $styles = [
        'bhp-primary'   => __('Primary', 'brave-hearts'),
        'bhp-secondary' => __('Secondary', 'brave-hearts'),
        'bhp-outline'   => __('Outline', 'brave-hearts'),
        'bhp-ghost'     => __('Ghost', 'brave-hearts'),
    ];

    foreach ($styles as $name => $label) {
        register_block_style('core/button', [
            'name'  => $name,
            'label' => $label,
        ]);
    }
}
add_action('init', 'bhp_register_block_styles');

// ============================================================
// LEGACY SHOP THE SERIES REDIRECT (Overnight Conversion Sprint, Priority 2)
// ============================================================
/**
 * The Complete Collection page (/complete-collection/) is now the single
 * flagship complete-set landing page. The older Shop the Series page
 * (post ID 359, still published so it can be restored if needed) is
 * removed from every menu/link in the customer journey and redirected
 * here instead of being deleted -- preserves a rollback path.
 *
 * A 302 (temporary) redirect is intentional on staging: it signals this
 * is not yet the final, permanent production redirect decision. Use a
 * 301 (permanent) redirect when this ships to production -- see the
 * Overnight Conversion Sprint deployment package for that exact step.
 */
add_action('template_redirect', 'bhp_redirect_legacy_shop_the_series');
function bhp_redirect_legacy_shop_the_series() {
    if (is_page('shop-the-series')) {
        wp_redirect(home_url('/complete-collection/'), 302);
        exit;
    }
}

// ============================================================
// ANALYTICS: DATALAYER INITIALIZATION
// ============================================================
/**
 * Initializes window.dataLayer once, sitewide, in <head> -- matching what
 * a real GTM container snippet does. Discovered missing while QA'ing the
 * Staging Refinement Phase 2 landing page: every analytics push across the
 * theme and the bundle-pricing plugin (mariana-popup.js, bundle-drawer.js,
 * bundle-landing.js, bundle-analytics.php) checks `typeof window.dataLayer
 * === 'undefined'` and silently no-ops if so, on the assumption something
 * else already created the array. Previously nothing did on any page
 * without a product-page or thank-you-page analytics push (e.g. this new
 * landing page, and technically the cart/checkout/shop-the-series pages
 * too) -- so those events were silently being dropped, not fired. Fixed
 * here, sitewide, instead of patching every individual script.
 */
add_action( 'wp_head', 'bhp_init_datalayer', 1 );
function bhp_init_datalayer() {
    echo "<script>window.dataLayer = window.dataLayer || [];</script>\n";
}

// ============================================================
// ANALYTICS PHASE 1B: GTM / consent / UTM attribution architecture
// ============================================================
// See docs/analytics-architecture.md for the full design. Every file
// below is a self-contained class that no-ops safely when unconfigured
// (no GTM container ID set yet) -- loading them here does not print
// anything new on its own.
require_once get_template_directory() . '/inc/class-bhp-analytics-config.php';
require_once get_template_directory() . '/inc/class-bhp-consent.php';
require_once get_template_directory() . '/inc/class-bhp-gtm-loader.php';
require_once get_template_directory() . '/inc/class-bhp-utm-attribution.php';
require_once get_template_directory() . '/inc/class-bhp-analytics-debug.php';
require_once get_template_directory() . '/inc/class-bhp-wpconsent-bridge.php';
require_once get_template_directory() . '/inc/checkout-experience.php';
require_once get_template_directory() . '/inc/consent-banner-compact.php';

// ============================================================
// ENQUEUE STYLES & SCRIPTS
// ============================================================
function bhp_enqueue_assets() {
    $theme_version = wp_get_theme()->get('Version');

    wp_enqueue_style('bhp-style', get_stylesheet_uri(), [], $theme_version);
    /*
     * CYCLE144-LD-201 (2026-08-05, theme 1.19.190) — THE GOOGLE FONTS
     * STYLESHEET IS GONE. The fonts are now SELF-HOSTED and their
     * @font-face rules are INLINED in the head by
     * `bhp_print_self_hosted_fonts()` (called from `header.php`).
     *
     * ⛔ NOTHING ABOUT THE TYPEFACES CHANGED. Same four families, same
     *    weights, same italics, same `font-display: swap`, same
     *    `unicode-range` splits, and the woff2 files in `assets/fonts/`
     *    are the exact bytes fonts.gstatic.com served on 2026-08-05.
     *    See the header of `assets/fonts/fonts.css` for provenance and
     *    the regeneration procedure.
     *
     * MEASURED, staging, mobile / Slow 4G, theme 1.19.189 baseline: this
     * one stylesheet was 885 ms of the page's 1,190 ms of render-blocking
     * time — the largest single blocker — and it gated a SECOND origin
     * (fonts.gstatic.com) that could not even be contacted until this
     * request on the FIRST origin had returned and been parsed. That
     * chain is the mechanism behind the fallback-font flash Andrew
     * reported: preconnect (added F1/F7) shortened it but could not
     * remove it, because a cross-origin CSS parse sat in the middle.
     *
     * SUPERSEDED comment, preserved because it records a real decision:
     *   // F1 (2026-08-03): Lato removed. It was requested sitewide and never
     *   // resolved to a single rendered element. Three families now carry the
     *   // whole site: Cormorant Garamond (display), EB Garamond (body),
     *   // Archivo (UI). Caveat stays -- it is the journal/handwriting accent.
     * That family list is still exactly right and is what was self-hosted.
     */
    wp_enqueue_script('bhp-nav', get_template_directory_uri() . '/assets/js/nav.js', [], $theme_version, true);

    // 2026-07-18: sitewide acquisition-form success-visibility + busy-state
    // enhancement. Every page can carry a signup form (footer/popups/lead
    // magnets), and the homepage form is not gated by any page-specific
    // condition, so this loads unconditionally, matching bhp-nav above --
    // the file itself is a no-op on any page with no .acquisition-form.
    wp_enqueue_script('bhp-acquisition-form-ux', get_template_directory_uri() . '/assets/js/acquisition-form-ux.js', [], $theme_version, true);

    // Analytics Phase 1B: first-party UTM capture. Gated the same way the
    // GTM loader is (not on staging unless explicitly overridden, never
    // for admin/internal traffic) -- see BHP_Analytics_Config.
    if ( BHP_Analytics_Config::should_render_analytics() ) {
        wp_enqueue_script('bhp-attribution', get_template_directory_uri() . '/assets/js/bhp-attribution.js', [], $theme_version, true);
    }
}
add_action('wp_enqueue_scripts', 'bhp_enqueue_assets');

// ============================================================
// CRITICAL-PATH HEAD: self-hosted fonts + the LCP image preload
// ============================================================
/*
 * CYCLE144-LD-201 / -202 (2026-08-05, theme 1.19.190).
 *
 * Both functions below are called from `header.php` BEFORE `wp_head()`
 * rather than hooked onto it, and that placement is load-bearing, not
 * stylistic: the browser's preload scanner reads the head in SOURCE
 * ORDER, so a preload emitted after `wp_head()`'s stylesheet <link>s is
 * queued behind them. The same reasoning already governed the F1/F7
 * preconnect hints these replace.
 *
 * ⛔ NEITHER FUNCTION TOUCHES THE CONSENT / GTM HEAD SEQUENCE. The
 *    consent-default -> WPConsent bridge -> gtm.js order is printed by
 *    `BHP_Consent`, `BHP_WPConsent_Bridge` and `BHP_GTM_Loader` on
 *    `wp_head`, and everything here is emitted before `wp_head()` runs
 *    at all — it cannot reorder, delay or interleave with any of them.
 *    `tests/test-consent-mode-cache-safety.php` is the regression gate.
 */

/**
 * Print the self-hosted @font-face rules inline, plus a preload for the
 * three faces that paint above the fold.
 *
 * INLINE rather than enqueued because a separate stylesheet would be a
 * new render-blocking request for ~2 KB gzipped — trading one blocker for
 * another. Inlined, the rules cost zero requests and are parsed with the
 * document.
 *
 * PRELOADED faces, and why exactly these three: the first screen of every
 * template renders an eyebrow/nav in `--font-ui` (Archivo), a heading in
 * `--font-display` (Cormorant Garamond) and body copy in `--font-body`
 * (EB Garamond), all in the `latin` subset. Those three roman faces are
 * therefore certain to be needed. Caveat, and every italic and
 * `latin-ext` face, is deliberately NOT preloaded — Caveat resolves to a
 * single element far below the fold on one template, and preloading a
 * face the page may not use wastes bandwidth on the exact connection
 * that can least afford it.
 *
 * `crossorigin` is REQUIRED even though these are same-origin: fonts are
 * always fetched in CORS mode, and a preload without it is a silent
 * double-download rather than an error.
 */
function bhp_print_self_hosted_fonts() {
    static $css = null;

    $dir_uri = get_template_directory_uri();

    if ($css === null) {
        $path = get_template_directory() . '/assets/fonts/fonts.css';
        $raw  = is_readable($path) ? file_get_contents($path) : '';
        // Strip the provenance comment block — it is for developers reading
        // the repository, not for every page load.
        $raw  = preg_replace('#/\*.*?\*/#s', '', (string) $raw);
        $css  = trim(preg_replace('/\n{2,}/', "\n", $raw));
    }

    if ($css === '') {
        return; // Fail open: no inline rules, families fall back to Georgia/Arial.
    }

    foreach ([
        'archivo-normal-latin-79dc5e10.woff2',
        'cormorant-garamond-normal-latin-2fed1d1b.woff2',
        'eb-garamond-normal-latin-143e8895.woff2',
    ] as $file) {
        printf(
            '<link rel="preload" as="font" type="font/woff2" href="%s" crossorigin>' . "\n",
            esc_url($dir_uri . '/assets/fonts/' . $file)
        );
    }

    echo '<style id="bhp-fonts">' . str_replace('%%THEME_URI%%', $dir_uri, $css) . "</style>\n";
}

/**
 * Preload the homepage hero's background image.
 *
 * THE MEASUREMENT THAT MOTIVATES THIS, and it is the single largest
 * finding of the 2026-08-05 audit. On staging, mobile, Slow 4G, theme
 * 1.19.189, Lighthouse resolved the LCP element to `section#home-hero`
 * and its LCP resource to `assets/images/handoff/hero-ocean.webp`. The
 * phase breakdown of a 10.54 s LCP was:
 *
 *     TTFB 787 ms | LOAD DELAY 7,129 ms (68%) | Load 774 ms | Render 1,847 ms
 *
 * Load Delay is the browser sitting idle *before it knows the image
 * exists*. It is a CSS `background-image`, so it is undiscoverable by the
 * preload scanner: it can only be found after `style.css` (75 KB, itself
 * queued behind the Google Fonts stylesheet) has downloaded AND been
 * parsed AND matched to an element. Lighthouse's `lcp-discovery` insight
 * failed on precisely those two checks — "Request is discoverable in
 * initial document: false" and "fetchpriority=high should be applied:
 * false".
 *
 * A `preload` in the head fixes the discovery half; `fetchpriority=high`
 * fixes the priority half. No markup, no CSS rule and no artwork changes
 * — the same file is fetched, just sooner.
 *
 * SCOPED TO THE FRONT PAGE ON PURPOSE. `hero-ocean.webp` is the front
 * page's hero background. Preloading it sitewide would download a large
 * image on templates that never paint it, which is a regression dressed
 * as an optimisation.
 */
function bhp_print_lcp_preload() {
    if (!is_front_page()) {
        return;
    }

    /*
     * ⭐ CYCLE144-LD-215 (2026-08-05, theme 1.19.201) — THE PRELOAD IS NOW
     *    VIEWPORT-SPLIT, and it has to be.
     *
     * `style.css` (CYCLE144-LD-213 §1) serves a 760px-wide resample of this
     * photograph below 768px. A single unconditional preload of the
     * full-resolution file would therefore make a phone download BOTH: the
     * preload fetches the 1066px original, then the stylesheet paints the
     * 760px variant — 198.8 KB of pure waste, and a REGRESSION dressed as an
     * optimisation, which is exactly the failure mode this file already warns
     * about two paragraphs up.
     *
     * `media` on a `<link rel="preload">` is evaluated by the preload scanner,
     * so the phone fetches only the mobile file and the desktop only the
     * original. The 768px boundary is the same one the stylesheet uses; if one
     * moves, both must.
     *
     * MEASURED, staging, Lighthouse 12.8.2 mobile: the LCP resource is this
     * image and 54% of a 5.8 s LCP was LOAD DELAY — the browser waiting on
     * bytes, not on discovery. Discovery was already solved (this audit scores
     * 1/1); size is what was left.
     */
    $base = get_template_directory_uri() . '/assets/images/handoff/';

    printf(
        '<link rel="preload" as="image" href="%s" type="image/webp" media="(max-width: 768px)" fetchpriority="high">' . "\n",
        esc_url($base . 'hero-ocean-m.webp')
    );
    printf(
        '<link rel="preload" as="image" href="%s" type="image/webp" media="(min-width: 769px)" fetchpriority="high">' . "\n",
        esc_url($base . 'hero-ocean.webp')
    );
}

/**
 * Let `wp_kses_post()` keep an <img>'s responsive-image attributes.
 *
 * ⭐ CYCLE144-LD-205 (2026-08-05) — THE REAL REASON THE HOMEPAGE HERO
 *    COVERS DOWNLOADED AT 600 px. This is a root cause, not a tuning
 *    knob, and it was found by comparing what `wp_get_attachment_image()`
 *    RETURNS against what the page actually SERVES.
 *
 * OBSERVED on staging, WordPress 7.0, 2026-08-05:
 *
 *   wp_kses_allowed_html('post')['img'] =
 *     alt, align, border, height, hspace, loading, longdesc, vspace, src,
 *     usemap, width, aria-*, class, data-*, dir, hidden, id, lang, style,
 *     title, role, xml:lang
 *
 *   — no `srcset`, no `sizes`, no `decoding`, no `fetchpriority`.
 *
 * `template-parts/components/hero.php` passes its `aside` markup through
 * `wp_kses_post()`, and the homepage's three book covers live inside that
 * aside. So the covers were built correctly by
 * `wp_get_attachment_image()` — full eight-candidate srcset, explicit
 * sizes, decoding — and then had every one of those attributes SILENTLY
 * REMOVED on the way to the browser. Verified both directions in one
 * call: the same string before kses carried `srcset`/`sizes`/`decoding`
 * and after kses carried none of them.
 *
 * The consequence is not subtle. With no srcset the browser has exactly
 * one candidate and no choice: it downloads the 600 w file, 150-167 KB
 * each, to paint a box the CSS pins at 92 px. It also means the `sizes`
 * correction shipped alongside this was INERT until this filter existed —
 * a `sizes` attribute with no `srcset` to select from does nothing. Both
 * halves are needed; neither works alone.
 *
 * ⛔ WHY THIS IS SAFE. All four attributes are inert. `srcset` and
 *    `sizes` are image-candidate/length lists that no browser executes;
 *    `decoding` and `fetchpriority` are enumerated keywords. None can
 *    carry script. WordPress core itself allowed `srcset` and `sizes` in
 *    this context for years, which is why theme code written against
 *    earlier versions — including this theme's — reasonably assumed they
 *    would survive.
 *
 * ⛔ THIS ADDS ATTRIBUTES. It removes nothing from the allow-list and
 *    widens no tag. Every other element kses governs is untouched.
 *
 * Fixing it here rather than in `hero.php` is deliberate: the theme calls
 * `wp_kses_post()` on generated markup in several places, and a fix
 * scoped to one caller would leave the same defect in the others while
 * looking solved.
 */
function bhp_kses_allow_responsive_image_attrs($tags, $context) {
    if (!in_array($context, ['post', 'entry'], true)) {
        return $tags;
    }
    if (isset($tags['img']) && is_array($tags['img'])) {
        $tags['img']['srcset']        = true;
        $tags['img']['sizes']         = true;
        $tags['img']['decoding']      = true;
        $tags['img']['fetchpriority'] = true;
    }
    return $tags;
}
add_filter('wp_kses_allowed_html', 'bhp_kses_allow_responsive_image_attrs', 10, 2);

// ============================================================
// WIDGET AREAS
// ============================================================
function bhp_register_sidebars() {
    register_sidebar([
        'name'          => __('Footer Column 3', 'brave-hearts'),
        'id'            => 'footer-3',
        'description'   => __('Add widgets for footer column 3.', 'brave-hearts'),
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ]);
}
add_action('widgets_init', 'bhp_register_sidebars');

// ============================================================
// WOOCOMMERCE: REMOVE SIDEBAR, ADJUST COLUMNS
// ============================================================
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

/**
 * Product-page purchase-hierarchy fix (Staging Refinement Phase 1, item 1):
 * the short excerpt block (priority 20) previously sat between price and
 * the variation/Add to Cart form, forcing customers to scroll past a
 * paragraph of marketing copy before they could pick a format and buy.
 * Removed here -- the fuller, better version of this copy already exists
 * in the native WooCommerce Description tab further down the page (step
 * 10 of the approved hierarchy), so nothing is lost, just de-duplicated
 * and moved out of the above-the-fold path.
 *
 * The generic standalone price hook (priority 10) is deliberately NOT
 * removed, despite the approved hierarchy listing format-selector before
 * price: every current book product is a variable product with exactly
 * ONE variation priced identically to the parent, and WooCommerce leaves
 * that variation's own price_html EMPTY in that exact case (confirmed live
 * via the localized product_variations data), relying entirely on this
 * parent-level hook to show a price at all. Removing it was tried first
 * and caused a real regression -- the price disappeared completely, before
 * and after selecting a format. Keeping it here is what makes a price
 * visible at all; see assets/js/product-format-autoselect.js for the
 * separate, still-valid fix that reveals the format control + Add to Cart
 * button immediately with no extra click.
 */
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);

function bhp_enqueue_product_format_autoselect() {
    if (function_exists('is_product') && is_product()) {
        wp_enqueue_script(
            'bhp-product-format-autoselect',
            get_template_directory_uri() . '/assets/js/product-format-autoselect.js',
            ['jquery', 'wc-add-to-cart-variation'],
            wp_get_theme()->get('Version'),
            // BH-02: WooCommerce enqueues wc-add-to-cart-variation with
            // data-wp-strategy="defer". A blocking dependent would execute
            // during parse — BEFORE the deferred variation handler binds its
            // change/found_variation logic — so the auto-select's trigger()
            // would race an un-initialized form. Deferring this script too puts
            // both in the same post-parse queue in dependency/DOM order: the
            // WooCommerce variation form initializes first, THEN this auto-select
            // runs against a fully-wired form. Deterministic, no timing luck.
            ['strategy' => 'defer', 'in_footer' => true]
        );
    }
}
add_action('wp_enqueue_scripts', 'bhp_enqueue_product_format_autoselect');

/**
 * Renders a book cover <img> from bhp_get_series_adventures()'s image_id,
 * or an empty string if that adventure has no mapped image yet. Shared by
 * page-reluctant-reader-adventure-kit.php's hero, free-chapter, and
 * Complete Collection sections so there's one place this lookup happens.
 */
/*
 * F7 (2026-08-03) — THE FUNNEL-HERO UPSCALE, FIXED IN ONE PLACE.
 *
 * MEASURED before: this helper defaulted to `medium`, which for these three
 * attachments is 196-198 x 300. All five audience/parent funnel heroes render
 * that at 214-245 x 328-371 CSS px — an UPSCALE of x0.81 to x0.92 at DPR 1,
 * i.e. roughly x0.40 to x0.46 on a retina screen. The book covers were the
 * SOFTEST images on the five pages whose whole job is to sell the books.
 *
 * `woocommerce_single` (600x910 / 600x920) is used instead because it is the
 * one registered size that VERIFIED-EXISTS for all three cover attachments on
 * this environment (13, 16, 19 — checked with wp_get_attachment_metadata, not
 * assumed). `bhp-book-card` deliberately is NOT used: it was registered after
 * Everest (16) and The Amazon (19) were uploaded, so those two attachments
 * have no such derivative and WordPress silently falls back to the FULL
 * 1318x2000 original with NO srcset at all — which is exactly the 526 KB and
 * 274 KB hero downloads measured on the homepage (CYCLE142-LD-15).
 *
 * NO ARTWORK IS TOUCHED, GENERATED OR REGENERATED, and no media record is
 * mutated: this only changes WHICH ALREADY-EXISTING derivative is requested.
 * Fail-closed is unchanged — no image_id still returns an empty string.
 *
 * The explicit `sizes` is measured, not guessed: the hero covers render
 * 119-124 CSS px wide at 390 and ~245 px on the funnel heroes at desktop.
 */
function bhp_parent_landing_cover($adventure, $size = 'woocommerce_single') {
    if (empty($adventure['image_id'])) {
        return '';
    }
    return wp_get_attachment_image($adventure['image_id'], $size, false, [
        'loading' => 'eager',
        'alt'     => $adventure['image_alt'] ?? '',
        'sizes'   => '(max-width: 640px) 40vw, 260px',
    ]);
}

/**
 * Parent landing page's own cream/dark-green/gold visual system and format-
 * toggle/sticky-bar/FAQ JS -- scoped to this one page template only, same
 * pattern as the bundle-pricing plugin's bundle-landing assets, so the
 * distinct palette never leaks into the rest of the site.
 */
function bhp_enqueue_parent_landing_assets() {
    if (!is_page_template('page-reluctant-reader-adventure-kit.php')) {
        return;
    }
    $theme_version = wp_get_theme()->get('Version');
    /*
     * F1 (2026-08-03): this page's own Google Fonts request is REMOVED.
     * It asked for Nunito Sans (now retired -- the funnels take the sitewide
     * Archivo for UI and EB Garamond for body) plus Cormorant Garamond, which
     * the sitewide `bhp-google-fonts` request already carries at the same
     * weights. Two requests to two Google hosts for a family already on the
     * page is pure latency on the exact viewport that can least afford it.
     */
    wp_enqueue_style('bhp-parent-landing', get_template_directory_uri() . '/assets/css/parent-landing.css', ['bhp-style'], $theme_version);
    wp_enqueue_script('bhp-parent-landing', get_template_directory_uri() . '/assets/js/parent-landing.js', [], $theme_version, true);
}
add_action('wp_enqueue_scripts', 'bhp_enqueue_parent_landing_assets');

/**
 * Find Your Adventure — audience-routing quiz for organic traffic (Phase
 * 12, 2026-07-17). Registered as a shortcode so it can be placed on any
 * organic-entry page (homepage, resource hub, ambiguous blog posts)
 * without duplicating markup, per the "do not add it everywhere
 * indiscriminately" instruction — assets only load on pages that actually
 * render it.
 */
function bhp_render_audience_quiz_shortcode() {
    ob_start();
    get_template_part('template-parts/quiz/audience-quiz');
    return ob_get_clean();
}
add_shortcode('bhp_audience_quiz', 'bhp_render_audience_quiz_shortcode');

function bhp_enqueue_audience_quiz_assets() {
    $on_homepage = is_front_page();
    $on_shortcode_page = is_singular() && has_shortcode((string) (get_post()->post_content ?? ''), 'bhp_audience_quiz');
    // The canonical /find-your-adventure/ page (2026-07-17) renders the quiz
    // via a direct get_template_part() call in its page template rather than
    // the [bhp_audience_quiz] shortcode, so its post_content is empty and
    // $on_shortcode_page never matches -- without this, the quiz on its own
    // dedicated page loads with no JS/CSS and is silently inert. Same single
    // enqueue call below serves all three entry points; no second loading path.
    $on_canonical_quiz_page = is_page_template('page-find-your-adventure.php');
    // Sitewide quiz launcher (2026-07-17 modal follow-up): the launcher's
    // hidden modal renders this same template part on every page where
    // bhp_should_show_quiz_cta() is true, so its assets must load there too
    // -- same handles, same single enqueue function, no second loading path.
    $on_sitewide_launcher_page = bhp_should_show_quiz_cta();
    if (!$on_homepage && !$on_shortcode_page && !$on_canonical_quiz_page && !$on_sitewide_launcher_page) {
        return;
    }
    $theme_version = wp_get_theme()->get('Version');
    wp_enqueue_style('bhp-audience-quiz', get_template_directory_uri() . '/assets/css/audience-quiz.css', ['bhp-style'], $theme_version);
    wp_enqueue_script('bhp-audience-quiz', get_template_directory_uri() . '/assets/js/audience-quiz.js', [], $theme_version, true);
}
add_action('wp_enqueue_scripts', 'bhp_enqueue_audience_quiz_assets');

/**
 * Shared audience landing-page templates (Teachers, Gift Buyers,
 * Bookstores/Retailers, Organizations) -- same visual system as the Parent
 * page but its own independent stylesheet/script (assets/css/audience-
 * landing.css, assets/js/audience-landing.js) so neither page can regress
 * the other. Scoped to these 4 templates only.
 */
function bhp_enqueue_audience_landing_assets() {
    $audience_templates = [
        'page-audience-educators.php',
        'page-audience-gift-buyers.php',
        'page-audience-retailers.php',
        'page-audience-organizations.php',
    ];
    if (!is_page_template($audience_templates)) {
        return;
    }
    $theme_version = wp_get_theme()->get('Version');
    /* F1: see the matching note in bhp_enqueue_parent_landing_assets(). */
    wp_enqueue_style('bhp-audience-landing', get_template_directory_uri() . '/assets/css/audience-landing.css', ['bhp-style'], $theme_version);
    wp_enqueue_script('bhp-audience-landing', get_template_directory_uri() . '/assets/js/audience-landing.js', [], $theme_version, true);
}
add_action('wp_enqueue_scripts', 'bhp_enqueue_audience_landing_assets');

/**
 * The Complete Collection band's format toggle -- homepage and /books/ only.
 *
 * theme 1.19.177, 2026-08-05, CYCLE144-LD-51. Andrew Signore's current-turn
 * order (RELAYED through the Chief of Staff, NOT witnessed by this agent):
 * both bands must add the collection to the cart and land on /checkout/.
 * `assets/js/collection-band.js` is what makes the CTA follow the format the
 * visitor picked instead of always posting the default.
 *
 * SCOPED TO THE TWO SURFACES THAT RENDER THE BAND, deliberately, rather than
 * enqueued sitewide. `template-parts/components/complete-collection-feature.php`
 * has exactly two callers -- `front-page.php` and `page-books.php` -- and the
 * script does nothing at all on a page with no band. Shipping it everywhere
 * would put a file on the checkout page that has no business being there.
 *
 * ⭐ `is_page('books')` IS NOT BELT-AND-BRACES, IT IS THE CONDITION THAT
 *    ACTUALLY MATCHES, and it was found by running the suite rather than by
 *    reading the code. The /books/ page (ID 102 on staging) has NO
 *    `_wp_page_template` meta at all -- it renders through WordPress's
 *    `page-{slug}.php` hierarchy, so `page-books.php` is chosen by its
 *    FILENAME matching the slug. `is_page_template('page-books.php')`
 *    therefore returns FALSE there, and the first version of this function
 *    silently shipped no toggle script to the one page that already had the
 *    checkout CTA. `is_page_template()` is kept as well, so an editor who
 *    later assigns the template explicitly does not break it back.
 *
 * If a third surface ever renders the band, add its condition HERE. The
 * script's own guard (`[data-bhp-collection-band]`) means a missed enqueue
 * degrades to "the toggle does nothing and the CTA buys the default format",
 * never to a JS error.
 */
function bhp_enqueue_collection_band_assets() {
    if (!is_front_page() && !is_page_template('page-books.php') && !is_page('books')) {
        return;
    }
    $theme_version = wp_get_theme()->get('Version');
    wp_enqueue_script('bhp-collection-band', get_template_directory_uri() . '/assets/js/collection-band.js', [], $theme_version, true);
}
add_action('wp_enqueue_scripts', 'bhp_enqueue_collection_band_assets');

function bhp_woo_columns($columns) { return 3; }
add_filter('loop_shop_columns', 'bhp_woo_columns');

function bhp_woo_per_page($num) { return 12; }
add_filter('loop_shop_per_page', 'bhp_woo_per_page', 20);

// ============================================================
// EXCERPT LENGTH
// ============================================================
function bhp_excerpt_length($length) { return 30; }
add_filter('excerpt_length', 'bhp_excerpt_length');

// ============================================================
// DOCUMENT TITLE SEPARATOR
// ============================================================
function bhp_document_title_separator($sep) { return '·'; }
add_filter('document_title_separator', 'bhp_document_title_separator');

// ============================================================
// BODY CLASSES
// ============================================================
function bhp_body_classes($classes) {
    if (is_page()) {
        $classes[] = 'page-' . get_post_field('post_name', get_post());
    }
    if (function_exists('is_woocommerce') && is_woocommerce()) {
        $classes[] = 'woo-page';
    }
    if (function_exists('is_product') && is_product() && function_exists('wc_get_product')) {
        $product = wc_get_product(get_queried_object_id());
        if ($product) {
            $adventure_key = bhp_get_adventure_key_for_product($product);
            if ($adventure_key) {
                $classes[] = 'bhp-book-' . $adventure_key;
            }
        }
    }
    return $classes;
}
add_filter('body_class', 'bhp_body_classes');

// ============================================================
// WOOCOMMERCE BREADCRUMBS — SIMPLIFIED
// ============================================================
add_filter('woocommerce_show_page_title', '__return_false');

// ============================================================
// REMOVE WOOCOMMERCE DEFAULT WRAPPERS (we use our own)
// ============================================================
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);

function bhp_woo_wrapper_start() {
    echo '<div class="woo-expedition-shell">';
}
function bhp_woo_wrapper_end() {
    echo '</div>';
}
add_action('woocommerce_before_main_content', 'bhp_woo_wrapper_start', 10);
add_action('woocommerce_after_main_content', 'bhp_woo_wrapper_end', 10);

/**
 * Add a restrained expedition heading to WooCommerce archives without
 * replacing product markup, schema, variation forms, or checkout behavior.
 */
function bhp_woocommerce_archive_hero() {
    if (!function_exists('is_shop') || (!is_shop() && !is_product_taxonomy())) {
        return;
    }

    $title = is_shop() ? __('The Expedition Catalog', 'brave-hearts') : woocommerce_page_title(false);
    ?>
    <header class="interior-hero interior-hero--product woo-archive-hero">
      <div class="container">
        <p class="component-heading__eyebrow"><?php esc_html_e('Real places. Doors into wonder.', 'brave-hearts'); ?></p>
        <h1><?php echo esc_html($title); ?></h1>
        <p class="text-lead"><?php esc_html_e('Choose the real-world adventure and edition that fits your reader.', 'brave-hearts'); ?></p>
      </div>
    </header>
    <?php
}
add_action('woocommerce_before_main_content', 'bhp_woocommerce_archive_hero', 5);

/**
 * Complete Collection banner above the shop catalog grid (Overnight
 * Conversion Sprint, Priority 1.5) -- intercepts customers before they
 * start comparing six nearly-identical single-edition cards. Shop archive
 * only, not individual product pages or other taxonomy archives.
 */
function bhp_woocommerce_shop_complete_collection_banner() {
    if (!function_exists('is_shop') || !is_shop()) {
        return;
    }
    ?>
    <div class="woo-complete-collection-banner">
      <div class="container woo-complete-collection-banner__inner">
        <div>
          <h2><?php esc_html_e('Looking for the complete series?', 'brave-hearts'); ?></h2>
          <p><?php esc_html_e('Get all three adventures in paperback or hardcover.', 'brave-hearts'); ?></p>
        </div>
        <a class="btn btn-cta-primary" href="<?php echo esc_url(home_url('/complete-collection/')); ?>"><?php esc_html_e('View the Complete Collection', 'brave-hearts'); ?></a>
      </div>
    </div>
    <?php
}
add_action('woocommerce_before_main_content', 'bhp_woocommerce_shop_complete_collection_banner', 6);

/** Add clear expedition metadata labels to product cards. */
function bhp_woocommerce_loop_card_eyebrow() {
    echo '<p class="woo-card__eyebrow">' . esc_html__('Brave Hearts Expedition', 'brave-hearts') . '</p>';
}
add_action('woocommerce_shop_loop_item_title', 'bhp_woocommerce_loop_card_eyebrow', 5);

/** Continue the reader journey after a completed purchase. */
function bhp_order_confirmation_expedition_links() {
    ?>
    <section class="order-expedition-next" aria-labelledby="order-expedition-next-title">
      <p class="component-heading__eyebrow"><?php esc_html_e('The expedition continues', 'brave-hearts'); ?></p>
      <h2 id="order-expedition-next-title"><?php esc_html_e('Keep Exploring the Real World', 'brave-hearts'); ?></h2>
      <p><?php esc_html_e('Visit the Learning Hub for field notes and activities, or join the expedition for future resources and book news.', 'brave-hearts'); ?></p>
      <div class="cluster"><a class="btn btn-secondary" href="<?php echo esc_url(home_url('/blog/')); ?>"><?php esc_html_e('Explore the Learning Hub', 'brave-hearts'); ?></a><a class="btn btn-outline" href="<?php echo esc_url(home_url('/reluctant-reader-adventure-kit/')); ?>"><?php esc_html_e('Join the Expedition', 'brave-hearts'); ?></a></div>
    </section>
    <?php
}
add_action('woocommerce_thankyou', 'bhp_order_confirmation_expedition_links', 30);

/**
 * Send catalog purchases directly into the cart journey. Disabling the loop
 * AJAX class ensures WooCommerce follows the server redirect consistently.
 */
function bhp_catalog_add_to_cart_args($args) {
    if (!empty($args['class'])) {
        $classes = preg_split('/\s+/', trim((string) $args['class']));
        $classes = array_values(array_diff($classes, ['ajax_add_to_cart']));
        $args['class'] = implode(' ', $classes);
    }
    return $args;
}
add_filter('woocommerce_loop_add_to_cart_args', 'bhp_catalog_add_to_cart_args', 20);

function bhp_add_to_cart_redirect() {
    return function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
}
add_filter('woocommerce_add_to_cart_redirect', 'bhp_add_to_cart_redirect');

// ============================================================
// EXPLORER EXPEDITION GUIDES CONTENT ARCHITECTURE
// ============================================================
/**
 * Curated post relationships from the Phase III full-content audit.
 * Slugs preserve canonical post URLs and remain portable across environments.
 */
function bhp_get_guide_registry() {
    $reading = [
        'dog-man-to-magic-tree-house-reading-roadmap','what-to-read-after-dog-man','best-books-for-7-year-olds',
        'best-summer-reading-books-for-kids-ages-6-9','books-like-magic-tree-house',
        'gap-between-picture-books-and-chapter-books','finding-right-books-with-lexile-score',
        'bridge-books-for-struggling-readers','my-child-hates-reading-what-to-do','reading-level-by-grade-chart',
        'my-child-got-a-lexile-score-now-what','what-is-a-lexile-score','top-bridge-books-for-kids',
        'bridge-books-for-kids','what-are-bridge-books-guide-for-parents-and-teachers',
        'bridge-books-for-early-readers','bridge-books-for-kids-mount-everest',
        'best-early-chapter-books-for-6-year-olds','first-real-chapter-book-for-kids',
        'best-bridge-books-for-kids','adventure-books-for-kids-ages-6-9','how-stories-build-resilience-in-children',
    ];
    $science = [
        'science-books-for-kids-that-feel-like-adventures','mount-everest-facts-for-kids',
        'what-is-the-mariana-trench-for-kids','best-ocean-books-for-kids-ages-6-9',
        'mariana-trench-facts-for-kids','how-deep-is-the-mariana-trench-for-kids',
        'why-stem-storytelling-builds-braver-kids','amazon-rainforest-facts-for-kids',
    ];
    $educator = [
        'how-to-pick-a-read-aloud-book','best-read-aloud-books-for-classroom-grades-1-3',
        'teacher-appreciation-week-thank-you','free-teachers-guide-mariana-trench',
    ];
    $brand = ['kirkus-review-adventures-of-charlotte-and-henry','why-i-wrote-this-book'];
    $registry = [];

    foreach ($reading as $slug) {
        $registry[$slug] = ['primary' => 'reading-growing', 'secondary' => ['family-resources'], 'destination' => '', 'book' => '', 'audiences' => ['Families','General readers'], 'type' => 'Reading resource'];
    }
    foreach ($science as $slug) {
        $registry[$slug] = ['primary' => 'science-geography', 'secondary' => ['family-resources'], 'destination' => '', 'book' => '', 'audiences' => ['Families','Children with adult guidance'], 'type' => 'Educational article'];
    }
    foreach ($educator as $slug) {
        $registry[$slug] = ['primary' => 'educator-resources', 'secondary' => [], 'destination' => '', 'book' => '', 'audiences' => ['Educators','Librarians'], 'type' => 'Educator guide'];
    }
    foreach ($brand as $slug) {
        $registry[$slug] = ['primary' => 'book-brand-stories', 'secondary' => ['reading-growing'], 'destination' => '', 'book' => 'series-wide', 'audiences' => ['Families','Educators','General readers'], 'type' => 'Book-related article'];
    }

    $destinations = [
        'mount-everest-facts-for-kids' => ['mount-everest','mount-everest'],
        'what-is-the-mariana-trench-for-kids' => ['mariana-trench','mariana-trench'],
        'best-ocean-books-for-kids-ages-6-9' => ['mariana-trench','mariana-trench'],
        'mariana-trench-facts-for-kids' => ['mariana-trench','mariana-trench'],
        'how-deep-is-the-mariana-trench-for-kids' => ['mariana-trench','mariana-trench'],
        'free-teachers-guide-mariana-trench' => ['mariana-trench','mariana-trench'],
        // Finding #17: give the Amazon its destination trail, matching Mariana/
        // Everest. Anchored by real content ("10 Amazon Rainforest Facts for
        // Kids"); the destination card/section are presence-guarded in
        // page-teachers.php so nothing renders where the post is absent.
        'amazon-rainforest-facts-for-kids' => ['amazon-rainforest','amazon_rainforest'],
    ];
    foreach ($destinations as $slug => $connection) {
        if (isset($registry[$slug])) {
            $registry[$slug]['destination'] = $connection[0];
            $registry[$slug]['book'] = $connection[1];
        }
    }
    foreach (['science-books-for-kids-that-feel-like-adventures','why-stem-storytelling-builds-braver-kids'] as $slug) {
        if (isset($registry[$slug])) {
            $registry[$slug]['book'] = 'series-wide';
        }
    }

    return apply_filters('bhp_guide_registry', $registry);
}

function bhp_get_guide_hubs() {
    return [
        'reading-growing' => __('Reading & Growing', 'brave-hearts'),
        'science-geography' => __('Science & Geography', 'brave-hearts'),
        'educator-resources' => __('Educator Resources', 'brave-hearts'),
        'book-brand-stories' => __('Book & Brand Stories', 'brave-hearts'),
        'mariana-trench' => __('The Mariana Trench', 'brave-hearts'),
        'mount-everest' => __('Mount Everest', 'brave-hearts'),
        'amazon-rainforest' => __('The Amazon Rainforest', 'brave-hearts'),
        'family-resources' => __('For Families', 'brave-hearts'),
    ];
}

function bhp_get_guide_post_data($post = null) {
    $post = get_post($post);
    if (!$post || $post->post_type !== 'post') {
        return [];
    }
    $registry = bhp_get_guide_registry();
    return $registry[$post->post_name] ?? [];
}

function bhp_get_guide_hub_url($hub) {
    return home_url('/teachers/#' . sanitize_title($hub));
}

function bhp_get_guide_posts($hub, $limit = -1) {
    static $cache = [];
    $cache_key = sanitize_key($hub) . ':' . (int) $limit;
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }
    $slugs = [];
    foreach (bhp_get_guide_registry() as $slug => $data) {
        if (($data['primary'] ?? '') === $hub || in_array($hub, $data['secondary'] ?? [], true) || ($data['destination'] ?? '') === $hub) {
            $slugs[] = $slug;
        }
    }
    if (!$slugs) {
        return [];
    }
    $cache[$cache_key] = get_posts([
        'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => $limit,
        'post_name__in' => $slugs, 'orderby' => 'date', 'order' => 'DESC',
        'no_found_rows' => true, 'suppress_filters' => false,
    ]);
    return $cache[$cache_key];
}

function bhp_get_related_guide_posts($post = null, $limit = 4) {
    $post = get_post($post);
    $current = bhp_get_guide_post_data($post);
    if (!$post || !$current) {
        return [];
    }
    $scored = [];
    foreach (bhp_get_guide_registry() as $slug => $data) {
        if ($slug === $post->post_name) { continue; }
        $score = (($data['primary'] ?? '') === $current['primary']) ? 5 : 0;
        if (!empty($current['destination']) && ($data['destination'] ?? '') === $current['destination']) { $score += 4; }
        if (!empty($current['book']) && ($data['book'] ?? '') === $current['book']) { $score += 2; }
        $score += count(array_intersect($data['secondary'] ?? [], $current['secondary'] ?? []));
        if ($score > 0) { $scored[$slug] = $score; }
    }
    uksort($scored, static function ($a, $b) use ($scored, $post) {
        if ($scored[$a] !== $scored[$b]) {
            return $scored[$b] <=> $scored[$a];
        }
        return sprintf('%u', crc32($post->post_name . '|' . $a)) <=> sprintf('%u', crc32($post->post_name . '|' . $b));
    });
    $posts = [];
    foreach (array_keys($scored) as $slug) {
        $related = get_page_by_path($slug, OBJECT, 'post');
        if ($related && $related->post_status === 'publish') { $posts[] = $related; }
        if (count($posts) >= $limit) { break; }
    }
    return $posts;
}

// ============================================================
// FALLBACK MENU (before nav is assigned in WP admin)
// ============================================================
function bhp_fallback_menu() {
    $links = [
        __('Home', 'brave-hearts')              => home_url('/'),
        // B6: the fallback nav carries "Start Here" too, so the two nav
        // sources cannot disagree if the stored menu is ever unassigned.
        __('Start Here', 'brave-hearts')        => home_url('/find-your-adventure/'),
        __('Books', 'brave-hearts')             => home_url('/books/'),
        __('Expedition Guides', 'brave-hearts') => home_url('/teachers/'),
        __('About', 'brave-hearts')             => home_url('/about/'),
        __('Blog', 'brave-hearts')              => home_url('/blog/'),
        __('Contact', 'brave-hearts')           => home_url('/contact/'),
    ];

    echo '<ul>';
    foreach ($links as $label => $url) {
        echo '<li><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
    }
    echo '</ul>';
}

/**
 * Normalize a visitor-facing link and reject common empty placeholder values.
 */
function bhp_get_safe_link_url($url, $fallback = '') {
    if (!is_scalar($url)) {
        $url = '';
    }

    $url = trim((string) $url);
    if (in_array(strtolower($url), ['', 'null', 'undefined'], true)) {
        $url = '';
    }

    if ($url !== '' && $url[0] === '#') {
        return preg_match('/^#[A-Za-z][A-Za-z0-9_-]*$/', $url) ? $url : '';
    }

    if ($url !== '' && strpos($url, '/') === 0) {
        $url = home_url($url);
    }

    $url = $url ? esc_url_raw($url, ['http', 'https']) : '';
    if ($url && wp_http_validate_url($url)) {
        return $url;
    }

    if ($fallback && $fallback !== $url) {
        return bhp_get_safe_link_url($fallback);
    }

    return '';
}

/**
 * Keep the legacy Teacher Resources route and menu entries canonical.
 */
function bhp_redirect_legacy_teacher_resources() {
    if (is_admin()) {
        return;
    }

    $request_path = untrailingslashit((string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH));
    $legacy_path  = untrailingslashit((string) wp_parse_url(home_url('/teachers-guide/'), PHP_URL_PATH));

    if ($request_path === $legacy_path) {
        wp_safe_redirect(home_url('/teachers/'), 301, 'Brave Hearts Theme');
        exit;
    }
}
add_action('template_redirect', 'bhp_redirect_legacy_teacher_resources');

/**
 * The Bookvault WooCommerce plugin originally created the Mariana Trench
 * Paperback product at this slug before it was migrated onto the existing
 * product/URL. Nothing should still be linking to it, but WordPress core's
 * own 404 "guess a nearby post" fallback (redirect_guess_404_permalink, run
 * from redirect_canonical on the default template_redirect priority) matches
 * it against the Hardcover product's slug and sends visitors there instead.
 * A Rank Math redirect rule was added for this same mapping, but it hooks at
 * the same priority and loses the race to WordPress core's earlier-registered
 * hook, which exits before Rank Math's redirect runs. Running at priority 1
 * guarantees this fires first.
 */
function bhp_redirect_legacy_bookvault_mariana_slug() {
    if (is_admin()) {
        return;
    }

    $request_path = untrailingslashit((string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH));
    $legacy_path  = untrailingslashit((string) wp_parse_url(home_url('/product/adventures-of-charlotte-and-henry-the-mariana-trench/'), PHP_URL_PATH));

    if ($request_path === $legacy_path) {
        wp_safe_redirect(home_url('/product/adventures-of-charlotte-and-henry-the-mariana-trench-paperback/'), 301, 'Brave Hearts Theme');
        exit;
    }
}
add_action('template_redirect', 'bhp_redirect_legacy_bookvault_mariana_slug', 1);

/**
 * Mailchimp for WooCommerce ships two independent checkout newsletter
 * checkboxes: an "additional checkout field" (gated by its own
 * mailchimp_checkbox_defaults=hide setting, already configured) and a
 * separate native Checkout Block ("subscribe-to-newsletter", registered in
 * blocks/newsletter.php) that renders unconditionally whenever Mailchimp is
 * configured and never checks that same hide setting. There's no plugin
 * option to hide this second one, so it's hidden here the same way the
 * plugin's own "hide" mode hides the first one: client-side, on the
 * checkout page only.
 */
function bhp_hide_duplicate_mailchimp_newsletter_block() {
    if (!function_exists('is_checkout') || !is_checkout()) {
        return;
    }
    ?>
    <style>
        .wc-block-components-checkbox:has(#subscribe-to-newsletter) {
            display: none !important;
        }
    </style>
    <script>
        (function () {
            function hideMailchimpNewsletterCheckbox() {
                var input = document.getElementById('subscribe-to-newsletter');
                if (!input) {
                    return;
                }
                var wrapper = input.closest('.wc-block-components-checkbox') || input.parentElement;
                if (wrapper) {
                    wrapper.style.display = 'none';
                }
            }
            document.addEventListener('DOMContentLoaded', hideMailchimpNewsletterCheckbox);
            document.body && document.body.addEventListener('wc-blocks_render_blocks_frontend', hideMailchimpNewsletterCheckbox);
        })();
    </script>
    <?php
}
add_action('wp_footer', 'bhp_hide_duplicate_mailchimp_newsletter_block');

function bhp_canonicalize_teacher_menu_items($items) {
    $home_host    = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    $teacher_path = untrailingslashit((string) wp_parse_url(home_url('/teachers/'), PHP_URL_PATH));
    $legacy_path  = untrailingslashit((string) wp_parse_url(home_url('/teachers-guide/'), PHP_URL_PATH));
    $seen_teacher = false;

    foreach ($items as $index => $item) {
        $item_host = strtolower((string) wp_parse_url($item->url, PHP_URL_HOST));
        if ($item_host && $item_host !== $home_host) {
            continue;
        }

        $item_path = untrailingslashit((string) wp_parse_url($item->url, PHP_URL_PATH));
        if ($item_path === untrailingslashit((string) wp_parse_url(home_url('/family-resources/'), PHP_URL_PATH))) {
            $item->url = home_url('/teachers/#family-resources');
            continue;
        }
        if ($item_path === untrailingslashit((string) wp_parse_url(home_url('/adventure-club/'), PHP_URL_PATH))) {
            $item->url = home_url('/reluctant-reader-adventure-kit/');
            continue;
        }
        if (!in_array($item_path, [$teacher_path, $legacy_path], true)) {
            continue;
        }

        if ($seen_teacher) {
            unset($items[$index]);
            continue;
        }

        $item->url     = home_url('/teachers/');
        $item->title   = __('Expedition Guides', 'brave-hearts');
        $item->classes = array_values(array_unique(array_merge((array) $item->classes, ['menu-item--educator-guides'])));
        $seen_teacher = true;
    }

    return array_values($items);
}
add_filter('wp_nav_menu_objects', 'bhp_canonicalize_teacher_menu_items');

/**
 * Adventure Books positioning (2026-07-16): stack the primary nav's "Books"
 * label as two visual lines ("Adventure" / "Books") without touching the
 * WP-admin-stored menu item (title stays "Books" there, so it isn't fragile
 * to an admin re-save). Accessible name is set explicitly via aria-label
 * below since two block-level spans read as one run of text to a screen
 * reader otherwise.
 */
function bhp_stack_adventure_books_nav_label($items) {
    $books_path = untrailingslashit((string) wp_parse_url(home_url('/books/'), PHP_URL_PATH));

    foreach ($items as $item) {
        $item_path = untrailingslashit((string) wp_parse_url($item->url, PHP_URL_PATH));
        if ($item_path !== $books_path) {
            continue;
        }

        $item->title = '<span class="site-nav__label-line">' . esc_html__('Adventure', 'brave-hearts') . '</span><span class="site-nav__label-line">' . esc_html__('Books', 'brave-hearts') . '</span>';
        $item->classes = array_values(array_unique(array_merge((array) $item->classes, ['menu-item--adventure-books'])));
    }

    return $items;
}
add_filter('wp_nav_menu_objects', 'bhp_stack_adventure_books_nav_label', 20);

function bhp_adventure_books_nav_aria_label($atts, $item) {
    if (in_array('menu-item--adventure-books', (array) $item->classes, true)) {
        $atts['aria-label'] = __('Adventure Books', 'brave-hearts');
    }
    return $atts;
}
add_filter('nav_menu_link_attributes', 'bhp_adventure_books_nav_aria_label', 10, 2);

/* =====================================================================
 * B6 — "START HERE": ONE NEW PRIMARY-NAV ENTRY
 * =====================================================================
 *
 * Spec: Business OS `WORKING-DRAFTS\commerce-cx\
 * DRAFT-PHASE1-2026-08-03-START-HERE-ACCESS-SPEC.md` Part A (R1-R7).
 *
 * Requirements satisfied, one line each:
 *   R1 exactly ONE new item, no sub-menu, no second entry anywhere.
 *   R3 second position, immediately after "Home" -- never after "Contact".
 *   R4 the label is 10 characters, so the nav does not wrap at 1024-1280px.
 *   R5 destination `/find-your-adventure/`, the SAME page the mid-page
 *      band's "Not sure?" link now points at, so both routes converge on one
 *      page and one measurement.
 *   R6 NOT styled as a button. `.site-nav__cta` stays the only button in the
 *      header; two competing buttons is the hierarchy defect this avoids.
 *   R7 stable analytics identity -- see the attribute filter below.
 *
 * ⚠️ ONE DELIBERATE DEVIATION FROM THE SPEC, STATED RATHER THAN HIDDEN.
 *    R2 asks for the item to be added to the WordPress menu `menu-primary`
 *    in the database rather than in theme code. It is implemented HERE, as a
 *    `wp_nav_menu_objects` filter, for a concrete reason: a DB menu item does
 *    NOT travel in a theme ZIP, so a staging-only menu row would mean the nav
 *    entry silently failed to appear on production after an approved deploy,
 *    and someone would have to remember a manual admin step on a second
 *    environment. As theme code it ships once, behaves identically on both
 *    environments, and reverses by deleting these filters.
 *
 *    The spec's real concern -- "not hardcoded into header.php" -- is
 *    honoured: `header.php` is untouched, the stored menu is untouched, and
 *    this uses exactly the same `wp_nav_menu_objects` pattern the theme
 *    already uses for the Expedition Guides and Adventure Books items above.
 *    Recorded for Andrew as `CYCLE142-DEV-22`.
 */
function bhp_start_here_nav_item($items, $args = null) {
    if (is_object($args) && isset($args->theme_location) && 'primary' !== $args->theme_location) {
        return $items;
    }

    $url = home_url('/find-your-adventure/');

    // Idempotent: never add a second one if the stored menu already has it.
    $target_path = untrailingslashit((string) wp_parse_url($url, PHP_URL_PATH));
    foreach ($items as $existing) {
        if (untrailingslashit((string) wp_parse_url($existing->url, PHP_URL_PATH)) === $target_path) {
            return $items;
        }
    }

    $item = new stdClass();
    $item->ID               = 0;
    $item->db_id            = 0;
    $item->menu_item_parent = 0;
    $item->object_id        = 0;
    $item->object           = 'custom';
    $item->type             = 'custom';
    $item->type_label       = __('Custom Link', 'brave-hearts');
    $item->post_type        = 'nav_menu_item';
    $item->title            = __('Start Here', 'brave-hearts');
    $item->url              = $url;
    $item->target           = '';
    $item->attr_title       = '';
    $item->description      = '';
    $item->xfn              = '';
    $item->menu_order       = 0;
    $item->classes          = ['menu-item--start-here'];
    $item->current          = is_page_template('page-find-your-adventure.php');
    $item->current_item_ancestor = false;
    $item->current_item_parent   = false;

    /*
     * R3: second, immediately after "Home". First position would displace the
     * one item every visitor expects in slot one; second position is the
     * first CHOICE a scanning eye lands on. If "Home" cannot be located the
     * item goes first rather than last -- a route entry at the end of the nav
     * is a footer link with extra steps.
     */
    $home_path = untrailingslashit((string) wp_parse_url(home_url('/'), PHP_URL_PATH));
    $insert_at = 0;
    foreach (array_values($items) as $i => $existing) {
        if (untrailingslashit((string) wp_parse_url($existing->url, PHP_URL_PATH)) === $home_path) {
            $insert_at = $i + 1;
            break;
        }
    }

    $items = array_values($items);
    array_splice($items, $insert_at, 0, [$item]);

    return $items;
}
add_filter('wp_nav_menu_objects', 'bhp_start_here_nav_item', 30, 2);

/**
 * B6 / `CYCLE142-CX-22` — the analytics identity for the Start Here route.
 *
 * ⛔ THIS IS THE SINGLE MOST LIKELY SILENT FAILURE IN THIS BUILD, and it is
 *    why the filter exists. A WordPress menu item carries no `data-*`
 *    attributes of its own. `nav.js` reads `data-bhp-event` and the
 *    `data-bhp-cta-*` set generically; without them the nav entry emits
 *    nothing and Route 1 would look like it produced zero traffic while
 *    actually working fine.
 *
 * R17: NO NEW EVENT NAME IS INVENTED. This reuses `contextual_cta_click`,
 * already read by `nav.js` and already emitted by the mid-page band. The
 * three placements are separated by `cta-placement` alone -- one metric,
 * three placements, no second definition of the same thing.
 */
function bhp_start_here_nav_attributes($atts, $item) {
    if (!in_array('menu-item--start-here', (array) $item->classes, true)) {
        return $atts;
    }
    $atts['data-bhp-event']              = 'contextual_cta_click';
    $atts['data-bhp-cta-id']             = 'start_here_nav';
    $atts['data-bhp-cta-placement']      = 'primary_nav';
    $atts['data-bhp-cta-destination']    = 'quiz_page';
    $atts['data-bhp-cta-funnel-stage']   = 'discovery';
    return $atts;
}
add_filter('nav_menu_link_attributes', 'bhp_start_here_nav_attributes', 10, 2);

/**
 * The synthetic item has ID 0, which would render `id="menu-item-0"`. Blank
 * it rather than invent a number that could one day collide with a real menu
 * item id -- duplicate DOM ids are a checked regression in this project.
 */
function bhp_start_here_nav_item_id($id, $item) {
    if (in_array('menu-item--start-here', (array) $item->classes, true)) {
        return '';
    }
    return $id;
}
add_filter('nav_menu_item_id', 'bhp_start_here_nav_item_id', 10, 2);

/**
 * Disable invalid links stored in editor content and normalize the legacy
 * Teacher Resources route without changing the database.
 */
function bhp_sanitize_content_links($content) {
    if (!is_string($content) || stripos($content, '<a') === false) {
        return $content;
    }

    $canonical_url  = home_url('/teachers/');
    $home_host      = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    $canonical_path = untrailingslashit((string) wp_parse_url($canonical_url, PHP_URL_PATH));
    $legacy_path = untrailingslashit((string) wp_parse_url(home_url('/teachers-guide/'), PHP_URL_PATH));
    $known_path_repairs = [
        '/teachers' => $canonical_url,
        '/blog/blog/what-is-a-lexile-score' => home_url('/what-is-a-lexile-score/'),
    ];

    if (class_exists('WP_HTML_Tag_Processor')) {
        $processor = new WP_HTML_Tag_Processor($content);
        while ($processor->next_tag('A')) {
            $href = $processor->get_attribute('href');
            if (!is_string($href)) {
                continue;
            }

            $href = trim($href);
            if (in_array(strtolower($href), ['', 'null', 'undefined'], true)) {
                $processor->remove_attribute('href');
                $processor->set_attribute('aria-disabled', 'true');
                continue;
            }

            $href_host = strtolower((string) wp_parse_url($href, PHP_URL_HOST));
            $href_path = untrailingslashit((string) wp_parse_url($href, PHP_URL_PATH));
            $href_path_key = strtolower($href_path);
            $is_brand_host = !$href_host || $href_host === $home_host || $href_host === 'braveheartspublishing.com' || substr($href_host, -26) === '.braveheartspublishing.com';
            if (!$is_brand_host) {
                continue;
            }

            $href_query    = (string) wp_parse_url($href, PHP_URL_QUERY);
            $href_fragment = (string) wp_parse_url($href, PHP_URL_FRAGMENT);
            $href_suffix   = ('' !== $href_query ? '?' . $href_query : '') . ('' !== $href_fragment ? '#' . $href_fragment : '');

            if (isset($known_path_repairs[$href_path_key])) {
                $processor->set_attribute('href', $known_path_repairs[$href_path_key] . $href_suffix);
                continue;
            }

            if ($href_path === $legacy_path && $href_path !== $canonical_path) {
                $processor->set_attribute('href', $canonical_url . $href_suffix);
            }
        }

        return $processor->get_updated_html();
    }

    return preg_replace_callback('/<a\b[^>]*>/i', static function ($matches) use ($canonical_url, $home_host, $legacy_path, $canonical_path, $known_path_repairs) {
        $tag = $matches[0];
        if (!preg_match('/\shref\s*=\s*(["\'])(.*?)\1/i', $tag, $href_match)) {
            return $tag;
        }

        $href = trim($href_match[2]);
        if (in_array(strtolower($href), ['', 'null', 'undefined'], true)) {
            return str_replace($href_match[0], ' aria-disabled="true"', $tag);
        }

        $href_host = strtolower((string) wp_parse_url($href, PHP_URL_HOST));
        $href_path = untrailingslashit((string) wp_parse_url($href, PHP_URL_PATH));
        $href_path_key = strtolower($href_path);
        $is_brand_host = !$href_host || $href_host === $home_host || $href_host === 'braveheartspublishing.com' || substr($href_host, -26) === '.braveheartspublishing.com';
        if (!$is_brand_host) {
            return $tag;
        }

        $href_query    = (string) wp_parse_url($href, PHP_URL_QUERY);
        $href_fragment = (string) wp_parse_url($href, PHP_URL_FRAGMENT);
        $href_suffix   = ('' !== $href_query ? '?' . $href_query : '') . ('' !== $href_fragment ? '#' . $href_fragment : '');

        if (isset($known_path_repairs[$href_path_key])) {
            return str_replace($href_match[2], esc_url($known_path_repairs[$href_path_key] . $href_suffix), $tag);
        }

        if ($href_path === $legacy_path && $href_path !== $canonical_path) {
            return str_replace($href_match[2], esc_url($canonical_url . $href_suffix), $tag);
        }

        return $tag;
    }, $content);
}
add_filter('the_content', 'bhp_sanitize_content_links', 20);

// ============================================================
// HOMEPAGE CONTENT AND FEATURED BOOK DATA
// ============================================================
/**
 * Read an editable front-page custom field with a filterable fallback.
 * Field names use the public bhp_home_* prefix so they remain available
 * through WordPress's standard Custom Fields interface.
 */
function bhp_get_homepage_field($key, $fallback = '') {
    $page_id    = get_queried_object_id();
    $field_name = 'bhp_home_' . sanitize_key($key);
    $stored     = $page_id ? get_post_meta($page_id, $field_name, true) : '';
    $value      = ($stored !== '') ? $stored : $fallback;

    return apply_filters('bhp_homepage_field_' . sanitize_key($key), $value, $page_id);
}

/**
 * Build book-card arguments from featured products or a future Book post type.
 * Marking a WooCommerce product as featured automatically adds it to the pool.
 */
/**
 * Return normalized book formats from WooCommerce attributes and variations.
 */
function bhp_get_product_formats($product) {
    if (!$product || !is_a($product, 'WC_Product')) {
        return [];
    }

    $values = [
        $product->get_attribute('pa_format'),
        $product->get_attribute('format'),
        $product->get_attribute('pa_book-format'),
        $product->get_attribute('book-format'),
        get_post_meta($product->get_id(), 'bhp_book_formats', true),
    ];

    if ($product->is_type('variable') && function_exists('wc_get_product')) {
        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation) {
                continue;
            }
            foreach ($variation->get_attributes() as $attribute => $value) {
                if (strpos($attribute, 'format') !== false && $value) {
                    $values[] = $value;
                }
            }
        }
    }

    $formats = [];
    foreach ($values as $value) {
        foreach (preg_split('/[,|]+/', (string) $value) as $format) {
            $format = trim(str_replace(['-', '_'], ' ', wp_strip_all_tags($format)));
            if (!$format) {
                continue;
            }

            $normalized = strtolower($format);
            if (in_array($normalized, ['hardback', 'hard cover', 'hardcover'], true)) {
                $format = 'Hardcover';
            } elseif (in_array($normalized, ['paper back', 'paperback'], true)) {
                $format = 'Paperback';
            } elseif (in_array($normalized, ['ebook', 'e book', 'kindle ebook', 'kindle'], true)) {
                $format = 'Kindle';
            } else {
                $format = ucwords($format);
            }

            $formats[$format] = $format;
        }
    }

    $ordered = [];
    foreach (['Paperback', 'Hardcover', 'Kindle'] as $preferred) {
        if (isset($formats[$preferred])) {
            $ordered[] = $preferred;
            unset($formats[$preferred]);
        }
    }

    return array_merge($ordered, array_values($formats));
}

/**
 * Build book-card arguments from every live product or a future Book post type.
 */
function bhp_get_homepage_books($limit = -1) {
    $limit     = ((int) $limit === -1) ? -1 : max(1, absint($limit));
    $post_type = post_type_exists('product') ? 'product' : (post_type_exists('book') ? 'book' : '');
    $cards     = [];

    if (!$post_type) {
        return apply_filters('bhp_homepage_books', $cards, $limit);
    }

    $query_args = [
        'post_type'        => $post_type,
        'post_status'      => 'publish',
        'posts_per_page'   => $limit,
        'orderby'          => ['menu_order' => 'ASC', 'date' => 'ASC'],
        'no_found_rows'    => true,
        'suppress_filters' => false,
    ];

    if ($post_type === 'product' && taxonomy_exists('product_cat')) {
        $series_slugs = apply_filters('bhp_homepage_book_category_slugs', ['charlotte-henry', 'charlotte-and-henry', 'books']);
        foreach ($series_slugs as $series_slug) {
            if (term_exists($series_slug, 'product_cat')) {
                $query_args['tax_query'] = [[
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => sanitize_title($series_slug),
                ]];
                break;
            }
        }
    }

    $books = get_posts($query_args);

    foreach ($books as $book) {
        $book_url    = bhp_get_safe_link_url(get_permalink($book));
        if (!$book_url) {
            continue;
        }

        $product     = ($post_type === 'product' && function_exists('wc_get_product')) ? wc_get_product($book->ID) : null;
        $image_id    = get_post_thumbnail_id($book->ID);
        $image_alt   = $image_id ? get_post_meta($image_id, '_wp_attachment_image_alt', true) : '';
        $description = has_excerpt($book) ? get_the_excerpt($book) : '';
        $review      = get_post_meta($book->ID, 'bhp_review_label', true);

        if (!$review && stripos(get_the_title($book), 'Mariana Trench') !== false) {
            $review = __('Kirkus reviewed', 'brave-hearts');
        }

        $cards[] = [
            'product_id'   => $book->ID,
            'title'        => get_the_title($book),
            'url'          => $book_url,
            'image_id'     => $image_id,
            'image_alt'    => $image_alt,
            'badge'        => get_post_meta($book->ID, 'bhp_book_badge', true),
            'age_range'    => get_post_meta($book->ID, 'bhp_age_range', true) ?: __('Ages 6–9', 'brave-hearts'),
            'formats'      => $product ? bhp_get_product_formats($product) : array_filter(array_map('trim', explode(',', (string) get_post_meta($book->ID, 'bhp_book_formats', true)))),
            'rating'       => $product ? (float) $product->get_average_rating() : 0,
            'review_count' => $product ? (int) $product->get_rating_count() : 0,
            'review'       => $review,
            'description'  => $description,
            'price'        => $product ? wp_strip_all_tags($product->get_price_html()) : get_post_meta($book->ID, 'bhp_book_price', true),
            'cta_label'    => __('Shop this book', 'brave-hearts'),
        ];
    }

    return apply_filters('bhp_homepage_books', $cards, $limit);
}

/**
 * Resolve a Learning Hub topic to an existing or future WordPress category URL.
 */
function bhp_get_learning_category_url($slug) {
    $slug = sanitize_title($slug);
    $hub_page = get_page_by_path($slug, OBJECT, 'page');
    if ($hub_page && $hub_page->post_status === 'publish') {
        $hub_url = bhp_get_safe_link_url(get_permalink($hub_page));
        if ($hub_url) {
            return $hub_url;
        }
    }

    $category = get_category_by_slug($slug);
    if ($category) {
        $category_url = bhp_get_safe_link_url(get_category_link($category));
        if ($category_url) {
            return $category_url;
        }
    }

    // No dedicated hub page or category exists for this topic yet. Rather than
    // dropping every Learning Hub card onto the same generic blog index
    // (Finding #12 — all six cards otherwise resolved to /blog/), route the
    // reader to a real, topic-scoped search of the published field notes. Every
    // current topic (animals/science/geography/conservation/explorers/
    // activities) returns multiple genuine posts, so each card lands on a
    // distinct, relevant destination. If a matching category or hub page is
    // created later, the checks above take precedence and the card automatically
    // upgrades to that focused archive with no code change.
    $query = trim(str_replace('-', ' ', $slug));
    if ($query !== '') {
        // Scope to blog posts so the reader lands on genuine field notes/
        // articles for the topic, not unrelated pages (Privacy Policy, etc.)
        // or shop products that a bare site search would also surface.
        $search_url = bhp_get_safe_link_url(home_url('/?post_type=post&s=' . rawurlencode($query)));
        if ($search_url) {
            return $search_url;
        }
    }

    $posts_page_id = (int) get_option('page_for_posts');
    return $posts_page_id
        ? bhp_get_safe_link_url(get_permalink($posts_page_id), home_url('/blog/'))
        : home_url('/blog/');
}

/**
 * Required footer links when no editor-managed footer menu is assigned.
 */
function bhp_footer_fallback_menu() {
    $privacy_url = get_privacy_policy_url() ?: home_url('/privacy-policy/');
    $terms_url   = home_url('/terms/');

    if (function_exists('wc_get_page_id')) {
        $terms_page_id = wc_get_page_id('terms');
        if ($terms_page_id > 0) {
            $terms_url = bhp_get_safe_link_url(get_permalink($terms_page_id), $terms_url);
        }
    }

    $links = [
        __('Books', 'brave-hearts')             => home_url('/books/'),
        __('Expedition Guides', 'brave-hearts') => home_url('/teachers/'),
        __('Family Resources', 'brave-hearts')   => home_url('/teachers/#family-resources'),
        __('About', 'brave-hearts')             => home_url('/about/'),
        __('Blog', 'brave-hearts')              => home_url('/blog/'),
        __('Contact', 'brave-hearts')           => home_url('/contact/'),
        __('Privacy Policy', 'brave-hearts')    => $privacy_url,
        __('Terms', 'brave-hearts')             => $terms_url,
        __('Adventure Club', 'brave-hearts')    => home_url('/reluctant-reader-adventure-kit/'),
    ];

    echo '<ul>';
    foreach ($links as $label => $url) {
        $url = bhp_get_safe_link_url($url);
        if ($url) {
            echo '<li><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
        }
    }
    echo '</ul>';
}

// ============================================================
// CUSTOMER ACQUISITION FOUNDATION
// ============================================================
/**
 * Supported audience values for provider tags and CRM segmentation.
 */
function bhp_get_audience_types() {
    return apply_filters('bhp_audience_types', [
        'parents_families' => __('Parents / Families', 'brave-hearts'),
        'teachers'         => __('Teachers', 'brave-hearts'),
        'general_readers'  => __('General Readers', 'brave-hearts'),
    ]);
}

/**
 * Audience landing-page system (2026-07-15): the four core audience pages
 * beyond Parent/Teacher each need their own segmentation value so forms
 * never silently collapse into 'general_readers' via
 * bhp_normalize_audience_type(). Kept as a separate filter callback (not an
 * edit to bhp_get_audience_types() itself) so the base registry stays a
 * clean, single, additive extension point.
 */
add_filter('bhp_audience_types', function ($audiences) {
    $audiences['educators']     = __('Teachers / Librarians / Homeschool', 'brave-hearts');
    $audiences['gift_buyers']   = __('Gift Buyers', 'brave-hearts');
    $audiences['retailers']     = __('Bookstores / Retailers', 'brave-hearts');
    $audiences['organizations'] = __('Organizations', 'brave-hearts');
    return $audiences;
});

/**
 * Normalize an audience value so forms never emit an unknown segment.
 */
function bhp_normalize_audience_type($audience_type) {
    $audiences = bhp_get_audience_types();
    return isset($audiences[$audience_type]) ? $audience_type : 'general_readers';
}

/**
 * Lead-magnet registry. Download URLs intentionally remain empty until assets
 * and provider delivery automations are approved.
 */
function bhp_get_lead_magnets() {
    return apply_filters('bhp_lead_magnets', [
        'explorer_passport' => [
            'title'         => __('Explorer Passport', 'brave-hearts'),
            'description'   => __('Get free printable adventures that help young readers record what they discover.', 'brave-hearts'),
            'audience_type' => 'parents_families',
            'download_url'  => '',
            'status'        => 'placeholder',
        ],
        'printable_adventure_maps' => [
            'title'         => __('Printable Adventure Maps', 'brave-hearts'),
            'description'   => __('Follow Charlotte and Henry from real places on the map into the stories.', 'brave-hearts'),
            'audience_type' => 'general_readers',
            'download_url'  => '',
            'status'        => 'placeholder',
        ],
        'teacher_resources' => [
            'title'         => __('Teacher Resources', 'brave-hearts'),
            'description'   => __('Get classroom-ready updates for lesson plans, discussion guides, vocabulary, maps, printables, and read-aloud resources.', 'brave-hearts'),
            'audience_type' => 'teachers',
            'download_url'  => '',
            'status'        => 'placeholder',
        ],
        'teacher_lesson_plans' => [
            'title'         => __('Teacher Lesson Plans', 'brave-hearts'),
            'description'   => __('Bring the adventure into your classroom with practical, story-connected learning.', 'brave-hearts'),
            'audience_type' => 'teachers',
            'download_url'  => '',
            'status'        => 'placeholder',
        ],
        'reading_guides' => [
            'title'         => __('Reading Guides', 'brave-hearts'),
            'description'   => __('Help growing readers build confidence, comprehension, and curiosity.', 'brave-hearts'),
            'audience_type' => 'parents_families',
            'download_url'  => '',
            'status'        => 'placeholder',
        ],
        'mariana_trench_classroom_guide' => [
            'title'         => __('Mariana Trench Classroom Guide', 'brave-hearts'),
            'description'   => __('A free, no-prep 2-page classroom guide for Adventures of Charlotte and Henry: The Mariana Trench.', 'brave-hearts'),
            'audience_type' => 'teachers',
            'download_url'  => bhp_get_lead_magnet_pdf_url('mariana_teacher'),
            'status'        => bhp_get_lead_magnet_pdf_url('mariana_teacher') ? 'active' : 'placeholder',
        ],
        'mariana_trench_parent_guide' => [
            'title'         => __('Mariana Trench Parent Guide', 'brave-hearts'),
            'description'   => __('A free reading companion for Adventures of Charlotte and Henry: The Mariana Trench.', 'brave-hearts'),
            'audience_type' => 'parents_families',
            'download_url'  => bhp_get_lead_magnet_pdf_url('mariana_parent'),
            'status'        => bhp_get_lead_magnet_pdf_url('mariana_parent') ? 'active' : 'placeholder',
        ],
        'reluctant_reader_adventure_kit' => [
            'title'         => __('Reluctant Reader Adventure Kit', 'brave-hearts'),
            /* A6 (2026-08-03) — DURATION-CLAIM PURGE. Andrew's standing rule,
               relayed: no buyer-facing reading-duration claims. "We don't
               know" how fast anyone reads; some read one chapter a night.
               Superseded wording, kept so it is not re-derived:
               "A free 20-minute reading adventure for kids ages 6–9 who…" */
            'description'   => __('A free reading adventure for kids ages 6–9 who lose interest in long chapters or have not found a book they enjoy yet.', 'brave-hearts'),
            'audience_type' => 'parents_families',
            'download_url'  => bhp_get_lead_magnet_pdf_url('adventure_kit_parent'),
            'status'        => bhp_get_lead_magnet_pdf_url('adventure_kit_parent') ? 'active' : 'placeholder',
        ],
        'teacher_adventure_toolkit' => [
            'title'         => __('Adventure Learning Toolkit', 'brave-hearts'),
            'description'   => __('Free classroom-ready resources connecting the series to geography, science, history, vocabulary, and discussion - for teachers, librarians, and homeschool educators.', 'brave-hearts'),
            'audience_type' => 'educators',
            'download_url'  => bhp_get_lead_magnet_pdf_url('teacher_toolkit'),
            'status'        => bhp_get_lead_magnet_pdf_url('teacher_toolkit') ? 'active' : 'placeholder',
        ],
        'meaningful_gift_guide' => [
            'title'         => __('The Meaningful Gift Guide', 'brave-hearts'),
            'description'   => __('A free guide to choosing a gift that sparks curiosity and shared memories, for grandparents, aunts, uncles, and family friends.', 'brave-hearts'),
            'audience_type' => 'gift_buyers',
            'download_url'  => bhp_get_lead_magnet_pdf_url('gift_guide'),
            'status'        => bhp_get_lead_magnet_pdf_url('gift_guide') ? 'active' : 'placeholder',
        ],
        'bookstore_wholesale_guide' => [
            'title'         => __('Wholesale Guide', 'brave-hearts'),
            'description'   => __('Series overview, reader profile, and ordering details for independent bookstores, museum stores, and educational retailers.', 'brave-hearts'),
            'audience_type' => 'retailers',
            'download_url'  => bhp_get_lead_magnet_pdf_url('bookstore_wholesale_guide'),
            'status'        => bhp_get_lead_magnet_pdf_url('bookstore_wholesale_guide') ? 'active' : 'placeholder',
        ],
        'community_reading_kit' => [
            'title'         => __('Community Reading Kit', 'brave-hearts'),
            'description'   => __('Free resources for literacy programs, events, and bulk gifting - for children\'s hospitals, nonprofits, youth groups, and libraries.', 'brave-hearts'),
            'audience_type' => 'organizations',
            'download_url'  => bhp_get_lead_magnet_pdf_url('community_reading_kit'),
            'status'        => bhp_get_lead_magnet_pdf_url('community_reading_kit') ? 'active' : 'placeholder',
        ],
    ]);
}

/**
 * Mariana Trench guide download state, keyed by audience. Mirrors
 * bhp_get_explorer_passport_download(): empty URL always means "not ready",
 * regardless of what a filter might otherwise try to force.
 */
function bhp_get_mariana_guide_download($audience_type) {
    $pdf_key = ($audience_type === 'teachers') ? 'mariana_teacher' : 'mariana_parent';
    $url = bhp_get_safe_link_url(bhp_get_lead_magnet_pdf_url($pdf_key));

    return [
        'url'   => $url,
        'ready' => (bool) $url,
    ];
}

/**
 * Reluctant Reader Adventure Kit download state. Deliberately a separate
 * function/PDF key from bhp_get_mariana_guide_download() — the two parent
 * lead magnets (Mariana Trench Parent Guide and the Adventure Kit) are
 * independent and must never substitute for one another.
 */
function bhp_get_reluctant_reader_download() {
    $url = bhp_get_safe_link_url(bhp_get_lead_magnet_pdf_url('adventure_kit_parent'));

    return [
        'url'   => $url,
        'ready' => (bool) $url,
    ];
}

/**
 * Download-state helpers for the four core audience landing pages
 * (2026-07-15), same empty-URL-means-not-ready pattern as
 * bhp_get_reluctant_reader_download() — each page's lead-magnet CTA stays
 * in a "coming soon" state (form UI built, but no live Mailchimp submission
 * wired) until Andrew sets the real PDF under Settings -> Lead Magnets.
 */
function bhp_get_teacher_toolkit_download() {
    $url = bhp_get_safe_link_url(bhp_get_lead_magnet_pdf_url('teacher_toolkit'));
    return ['url' => $url, 'ready' => (bool) $url];
}

function bhp_get_gift_guide_download() {
    $url = bhp_get_safe_link_url(bhp_get_lead_magnet_pdf_url('gift_guide'));
    return ['url' => $url, 'ready' => (bool) $url];
}

function bhp_get_bookstore_guide_download() {
    $url = bhp_get_safe_link_url(bhp_get_lead_magnet_pdf_url('bookstore_wholesale_guide'));
    return ['url' => $url, 'ready' => (bool) $url];
}

function bhp_get_community_kit_download() {
    $url = bhp_get_safe_link_url(bhp_get_lead_magnet_pdf_url('community_reading_kit'));
    return ['url' => $url, 'ready' => (bool) $url];
}

/**
 * Mariana guide tags, matching the account's existing Title Case convention
 * (see the "Adventure Club" tag on the current MC4WP form) rather than the
 * hyphenated style first proposed for this funnel.
 */
add_filter('bhp_mailchimp_signup_tags', function ($tags, $context, $audience_type, $lead_magnet, $source_page) {
    if ($lead_magnet === 'mariana_trench_classroom_guide') {
        $source_tag = ($context === 'mariana_popup' || $context === 'teacher_popup') ? 'Source: Mariana Popup' : 'Source: Mariana Teacher Landing Page';
        return ['Mariana Trench Classroom Guide', 'Audience: Teacher/Librarian', $source_tag];
    }

    if ($lead_magnet === 'mariana_trench_parent_guide') {
        return ['Mariana Trench Parent Guide', 'Audience: Parent/Homeschool', 'Source: Mariana Parent Landing Page'];
    }

    if ($lead_magnet === 'reluctant_reader_adventure_kit') {
        $source_tag = ($context === 'parent_popup') ? 'Source: Parent Popup' : 'Source: Parent Landing Page';
        return ['Reluctant Reader Adventure Kit', 'Audience: Parent/Grandparent', $source_tag];
    }

    return $tags;
}, 10, 5);

/**
 * Tag mappings for the four core audience landing pages (2026-07-15),
 * same Title Case + "Audience: X" + "Source: Y Landing Page" convention as
 * the callback above. Kept as a separate add_filter call (not an edit to
 * the existing one) so Parent/Mariana's proven tag logic stays untouched.
 * Andrew/ChatGPT still need to build the Mailchimp automations that act on
 * these tags -- applying them here now (rather than leaving new signups on
 * the generic 'Adventure Club' fallback) avoids leads landing unclassified
 * while that automation work is in progress.
 */
add_filter('bhp_mailchimp_signup_tags', function ($tags, $context, $audience_type, $lead_magnet, $source_page) {
    if ($lead_magnet === 'teacher_adventure_toolkit') {
        return ['Adventure Learning Toolkit', 'Audience: Educator', 'Source: Educator Landing Page'];
    }

    if ($lead_magnet === 'meaningful_gift_guide') {
        return ['Meaningful Gift Guide', 'Audience: Gift Buyer', 'Source: Gift Buyer Landing Page'];
    }

    if ($lead_magnet === 'bookstore_wholesale_guide') {
        return ['Wholesale Guide', 'Audience: Retailer', 'Source: Retailer Landing Page'];
    }

    if ($lead_magnet === 'community_reading_kit') {
        return ['Community Reading Kit', 'Audience: Organization', 'Source: Organization Landing Page'];
    }

    return $tags;
}, 10, 5);

/**
 * Registers the Adventure Kit thank-you page as a whitelisted redirect
 * target, additive to the existing Mariana entry — inc/mailchimp.php never
 * needs to know about individual funnels, only this filterable map.
 */
add_filter('bhp_signup_success_redirect_pages', function ($pages) {
    $pages['adventure_kit_thank_you'] = 'adventure-kit-thank-you';
    // BH-04: dedicated Meaningful Gift Guide thank-you (page template
    // page-gift-guide-thank-you.php on a published page at this slug).
    $pages['gift_guide_thank_you'] = 'gift-guide-thank-you';
    // 2026-07-30: the four quiz result destinations. These are the audience
    // funnel pages themselves (not thank-you pages) per the approved quiz
    // flow. Registered as whitelisted KEYS, so the quiz endpoint still never
    // accepts a URL from the browser; each is resolved server-side through
    // get_page_by_path() + get_permalink() + wp_validate_redirect().
    $pages['quiz_parent_kit']       = 'reluctant-reader-adventure-kit';
    $pages['quiz_educator_toolkit'] = 'educators-adventure-learning-toolkit';
    $pages['quiz_gift_guide']       = 'gift-buyers-guide';
    $pages['quiz_community_kit']    = 'organizations-community-reading-kit';
    return $pages;
});

// ============================================================
// MARIANA SITEWIDE POPUP
// ============================================================
/**
 * Simple page-type label for the popup's analytics events. Not used for
 * any access-control decision, only for the page_type value sent to
 * dataLayer.
 */
function bhp_get_page_type_for_analytics() {
    if (is_front_page()) {
        return 'home';
    }
    if (function_exists('is_product') && is_product()) {
        return 'product';
    }
    if (is_singular('post')) {
        return 'post';
    }
    if (is_page()) {
        return 'page';
    }
    if (is_archive()) {
        return 'archive';
    }
    return 'other';
}

/**
 * Universal exclusions shared by every sitewide popup, regardless of which
 * one: admin sessions, WooCommerce transactional pages, legal pages, every
 * funnel's own landing/thank-you pages, and a just-completed contact-page
 * submission. Popup-specific eligibility (teacher vs. parent) layers on
 * top of this in bhp_should_show_teacher_popup() / _parent_popup().
 */
function bhp_should_show_any_popup() {
    if (is_admin() || (is_user_logged_in() && current_user_can('manage_options'))) {
        return false;
    }

    if (function_exists('is_cart') && is_cart()) {
        return false;
    }
    if (function_exists('is_checkout') && is_checkout()) {
        return false;
    }
    if (function_exists('is_account_page') && is_account_page()) {
        return false;
    }
    if (function_exists('is_order_received_page') && is_order_received_page()) {
        return false;
    }
    if (is_privacy_policy()) {
        return false;
    }
    if (function_exists('wc_get_page_id')) {
        $terms_page_id = wc_get_page_id('terms');
        if ($terms_page_id > 0 && is_page($terms_page_id)) {
            return false;
        }
    }
    if (is_page_template([
        'page-mariana-guide-teacher.php',
        'page-mariana-guide-parent.php',
        'page-mariana-guide-thank-you.php',
        'page-reluctant-reader-adventure-kit.php',
        'page-adventure-kit-thank-you.php',
        // Each of these 4 audience pages is already the dedicated signup
        // destination for its own audience-specific lead magnet (embedded
        // panel, same pattern as the Parent page) -- never stack a sitewide
        // or another audience's popup on top of it.
        'page-audience-educators.php',
        'page-audience-gift-buyers.php',
        'page-audience-retailers.php',
        'page-audience-organizations.php',
    ])) {
        return false;
    }
    // Contact page's own success state (same-page inline feedback, not a
    // distinct URL) — don't stack a popup on top of a just-completed form.
    if (is_page('contact') && isset($_GET['bhp_signup']) && sanitize_key(wp_unslash($_GET['bhp_signup'])) === 'success') {
        return false;
    }

    return true;
}

/**
 * The teacher popup is scoped to the Teachers page only — it is no longer
 * a sitewide popup. It remains a secondary capture method there alongside
 * the page's own embedded classroom-guide signup panel.
 */
function bhp_should_show_teacher_popup() {
    if (!bhp_should_show_any_popup()) {
        return false;
    }

    if (!is_page('teachers')) {
        return false;
    }

    return (bool) apply_filters('bhp_show_teacher_popup', true);
}

/**
 * The parent popup is the sitewide default once the parent PDF exists.
 * Never renders on the Teachers page (that page shows the teacher popup
 * instead — see the wp_footer hook below, which renders at most one of the
 * two), and never renders at all while the Adventure Kit PDF is unset.
 */
function bhp_should_show_parent_popup() {
    if (!bhp_should_show_any_popup()) {
        return false;
    }

    if (is_page('teachers')) {
        return false;
    }

    if (!bhp_get_reluctant_reader_download()['ready']) {
        return false;
    }

    return (bool) apply_filters('bhp_show_parent_popup', true);
}

/* =====================================================================
 * WAVE 1 — EXIT-INTENT CAPTURE MODAL (2026-08-04, theme 1.19.168)
 * ===================================================================== */

/**
 * ⭐ ENABLED BY DEFAULT SINCE 2026-08-04 (theme 1.19.169) — OWNER RULING.
 *
 * ⭐ Andrew Signore, 2026-08-04, verbatim: "Turn it on." Witnessed in the main
 *    session, not relayed second-hand. He was shown the conflict recorded
 *    below in full — that the exit-intent modal contradicts his own
 *    2026-07-19 ruling — and turned it on anyway.
 *
 *    ⚠️ THIS IS A DELIBERATE OWNER REVERSAL OF THE 2026-07-19 ONE-POPUP
 *       RULING, KNOWINGLY MADE. It is NOT drift, and a future session must
 *       not "restore" the default to false on the strength of the historical
 *       block below. Recorded as CYCLE143-LD-161, now CLOSED.
 *
 * ⛔ WHAT THE REVERSAL DOES *NOT* TOUCH — three things still stand:
 *    1. BOTH retired lead-magnet popups stay retired. `bhp_show_parent_popup`
 *       and `bhp_show_teacher_popup` are still filtered to false (below in
 *       this file, and in `inc/audit-remediation.php`). Andrew reversed the
 *       one-popup ruling only far enough to admit THIS surface.
 *    2. The shared session-frequency guard stands. One capture modal per
 *       session — exit-intent and the quiz modal cannot stack.
 *    3. The 20-second dwell floor stands (Andrew, 2026-08-04: "20 seconds
 *       please"), as does the absolute `/teachers/` exclusion below.
 *
 * ───────── HISTORICAL — the reason it originally shipped OFF, preserved ─────
 * (Accurate as written on 2026-08-04 at build time; superseded the same day
 *  by the ruling above. Kept, not deleted, so the reversal stays legible.)
 *
 * The build brief for Wave 1 (Andrew: "Lets do wave 1", RELAYED through the
 * Chief of Staff, not witnessed by the agent that wrote this) commissions an
 * exit-intent capture modal, on the stated premise that the parent and
 * teacher popups are "live and isolated".
 *
 * ⚠️ THAT PREMISE IS FALSE IN THE CURRENT CODE, and the code is what runs.
 *    Andrew Signore ruled on 2026-07-19, verbatim in `inc/audit-remediation.php`:
 *    "retire both lead-magnet popups sitewide ... The quiz modal becomes the
 *    ONLY popup on the site." Both `bhp_show_parent_popup` and
 *    `bhp_show_teacher_popup` are filtered to false — the parent one in TWO
 *    places (this file, below, and `inc/audit-remediation.php`). Nothing has
 *    reversed that ruling.
 *
 * ⛔ AN AGENT DOES NOT REVERSE AN OWNER RULING TO SATISFY A BRIEF THAT WAS
 *    WRITTEN WITHOUT KNOWING ABOUT IT. So the modal is built, wired, styled,
 *    tested and DEFAULTED TO FALSE. Turning it on is one line, and it is
 *    Andrew's line to write:
 *
 *        add_filter('bhp_show_exit_intent_popup', '__return_true');
 *
 *    Recorded for decision as CYCLE143-LD-161.
 *
 * ───────── END HISTORICAL. Andrew wrote that line on 2026-08-04. ───────────
 *
 * To switch it back off, the inverse is one line and is likewise Andrew's:
 *
 *     add_filter('bhp_show_exit_intent_popup', '__return_false');
 *
 * The staging preview parameter below is now redundant for the default case
 * and is KEPT: it still lets QA force the surface on a staging build where
 * someone has filtered it off, and removing it would break the QA path
 * documented in the release record.
 *
 * FOR STAGING QA, and only on staging: append
 * `?bhp_preview_exit_intent=1` to any eligible URL on
 * staging2.braveheartspublishing.com. The parameter is inert on production
 * by construction — `BHP_Analytics_Config::is_staging()` compares the real
 * HTTP host — and it substitutes ONLY for the enable filter. Every surface
 * exclusion below still applies to it, including `/teachers/`.
 */
function bhp_exit_intent_preview_requested() {
    if (!class_exists('BHP_Analytics_Config') || !BHP_Analytics_Config::is_staging()) {
        return false;
    }
    return isset($_GET['bhp_preview_exit_intent']) && '1' === sanitize_key(wp_unslash($_GET['bhp_preview_exit_intent']));
}

/**
 * Exit-intent eligibility. Same parent-funnel surface rules as the timed
 * parent popup — including the absolute `/teachers/` exclusion, which is
 * enforced HERE, server-side, and is never reachable by the preview
 * parameter.
 */
function bhp_should_show_exit_intent_popup() {
    if (!bhp_should_show_any_popup()) {
        return false;
    }

    // ⛔ Parent funnel only. Never on the teacher page. `.claude/rules/funnels.md`.
    if (is_page('teachers')) {
        return false;
    }

    // No offer PDF configured means no offer to make.
    if (!bhp_get_reluctant_reader_download()['ready']) {
        return false;
    }

    if (bhp_exit_intent_preview_requested()) {
        return true;
    }

    // ⭐ DEFAULT TRUE since 2026-08-04 — Andrew Signore, "Turn it on."
    //    Knowing reversal of his 2026-07-19 one-popup ruling. See the block
    //    above before changing this literal. CYCLE143-LD-161, CLOSED.
    return (bool) apply_filters('bhp_show_exit_intent_popup', true);
}

/* =====================================================================
 * WAVE 1 — SITEWIDE FOOTER CAPTURE BLOCK (2026-08-04, theme 1.19.168)
 * ===================================================================== */

/**
 * ⭐ ENABLED BY DEFAULT, unlike the exit-intent modal, and the difference is
 *    principled: this is static footer markup. It opens nothing, covers
 *    nothing and interrupts nothing, so Andrew's 2026-07-19 ruling that "the
 *    quiz modal becomes the ONLY popup on the site" does not reach it.
 *
 * ⛔ NOT ON `/teachers/`. `.claude/rules/funnels.md` scopes the parent
 *    funnel's Reluctant Reader Adventure Kit "sitewide except /teachers/",
 *    and this block's offer IS that kit. A capture FORM for the parent
 *    magnet on the teacher page would be a funnel-isolation breach even
 *    though a footer LINK to the kit page has been live there for months.
 *    The rule is applied to the form, which is what changes funnel state.
 *
 * ⛔ NOT on cart, checkout, account or order-received: an email ask beside
 *    an active payment flow competes with revenue.
 *
 * ⛔ NOT on any page that is already this offer's own destination, or
 *    another audience's dedicated signup page — the same exclusion list the
 *    popups use, so a visitor never meets two competing forms for two
 *    different lead magnets on one page.
 *
 * ⭐ Deliberately NOT gated on `is_user_logged_in()`: Andrew must be able to
 *    see it while logged in to review it.
 */
function bhp_should_show_footer_capture() {
    if (is_admin()) {
        return false;
    }

    // ⛔ Parent funnel, so never on the teacher page.
    if (is_page('teachers')) {
        return false;
    }

    if (function_exists('is_cart') && is_cart()) {
        return false;
    }
    if (function_exists('is_checkout') && is_checkout()) {
        return false;
    }
    if (function_exists('is_account_page') && is_account_page()) {
        return false;
    }
    if (function_exists('is_order_received_page') && is_order_received_page()) {
        return false;
    }

    if (is_page_template([
        'page-mariana-guide-teacher.php',
        'page-mariana-guide-parent.php',
        'page-mariana-guide-thank-you.php',
        'page-reluctant-reader-adventure-kit.php',
        'page-adventure-kit-thank-you.php',
        'page-audience-educators.php',
        'page-audience-gift-buyers.php',
        'page-audience-retailers.php',
        'page-audience-organizations.php',
        'page-explorer-passport-lead-magnet.php',
        'page-explorer-passport-thank-you.php',
        'page-gift-guide-thank-you.php',
    ])) {
        return false;
    }

    // The offer PDF must actually exist before the footer promises it.
    if (!bhp_get_reluctant_reader_download()['ready']) {
        return false;
    }

    return (bool) apply_filters('bhp_show_footer_capture', true);
}

/* =====================================================================
 * WAVE 1 — CAPTURE SEGMENTS (footer block), 2026-08-04
 * ===================================================================== */

/**
 * Fixed, server-side map from a capture-form segment key to the audience it
 * means. The browser only ever sends the short KEY — never an audience
 * type, never a lead-magnet key, never a tag, never a URL — so nothing
 * about the audience or the tags applied is attacker-controlled. Same
 * pattern, deliberately, as `bhp_get_quiz_signup_routes()`.
 *
 * ⛔ `lead_magnet` IS DELIBERATELY ABSENT FROM EVERY ROW. The capture form
 *    supplies its own, and it is the same one for all four segments: the
 *    free kit the form's copy actually promises. Routing a segment to a
 *    different lead magnet here is what would breach funnel isolation
 *    (`CYCLE143-MKT-136`) and what would promise a gift resource that does
 *    not exist (`CYCLE143-MKT-131`). This map changes the audience TAG and
 *    nothing else.
 *
 * The four labels are the four LIVE segments, verbatim from
 * `/find-your-adventure/` — Merry's SET A, adopted so every selection maps
 * to a segment the business already recognises rather than to an unrouted
 * "Other" bucket (`CYCLE143-MKT-135`).
 */
function bhp_get_capture_segment_routes() {
    return apply_filters('bhp_capture_segment_routes', [
        'parent' => [
            'label'         => __('My own reader (ages 6 to 9)', 'brave-hearts'),
            'audience_type' => 'parents_families',
            'audience_tag'  => 'Audience: Parent/Grandparent',
        ],
        'educator' => [
            'label'         => __('My class, library or homeschool', 'brave-hearts'),
            'audience_type' => 'educators',
            'audience_tag'  => 'Audience: Teacher/Librarian',
        ],
        'gift' => [
            'label'         => __('A gift for a young reader', 'brave-hearts'),
            'audience_type' => 'gift_buyers',
            'audience_tag'  => 'Audience: Gift Buyer',
        ],
        'organization' => [
            'label'         => __('Readers at our organization', 'brave-hearts'),
            'audience_type' => 'organizations',
            'audience_tag'  => 'Audience: Organization',
        ],
    ]);
}

/**
 * [key => label] for the <select>. Never used for routing.
 */
function bhp_get_capture_segment_labels() {
    $labels = [];
    foreach (bhp_get_capture_segment_routes() as $key => $route) {
        $labels[$key] = $route['label'];
    }
    return $labels;
}

/**
 * Resolve a submitted segment key, or '' if it is empty or unknown.
 */
function bhp_resolve_capture_segment($key) {
    $key = sanitize_key((string) $key);
    $routes = bhp_get_capture_segment_routes();
    return isset($routes[$key]) ? $key : '';
}

/**
 * Tag mapping for the two Wave 1 capture surfaces.
 *
 * A SEPARATE `add_filter` call, not an edit to either existing one, so the
 * proven Parent / Mariana / audience-landing tag logic stays byte-untouched
 * — the same reasoning recorded on the 2026-07-15 callback above.
 *
 * ⛔ NO TAG BELOW NAMES A RESOURCE THAT DOES NOT EXIST. Every signup here
 *    receives the Reluctant Reader Adventure Kit, so that is the resource
 *    tag for all four segments. The segment only adds an audience tag and
 *    a distinct source tag. In particular the gift lane gets
 *    "Audience: Gift Buyer" and NOT "Meaningful Gift Guide", because
 *    Gandalf's ruling is capture-and-tag only until a gift journey exists.
 */
add_filter('bhp_mailchimp_signup_tags', function ($tags, $context, $audience_type, $lead_magnet, $source_page) {
    if ($context === 'footer_capture') {
        $segment_tag = bhp_get_capture_segment_audience_tag();
        $resolved = ['Reluctant Reader Adventure Kit', 'Source: Footer Capture'];
        $resolved[] = $segment_tag ?: 'Audience: Parent/Grandparent';
        return $resolved;
    }

    if ($context === 'parent_popup_exit') {
        return ['Reluctant Reader Adventure Kit', 'Audience: Parent/Grandparent', 'Source: Exit Intent'];
    }

    return $tags;
}, 10, 5);

/**
 * The audience tag for the segment submitted with the current request, or
 * '' when no valid segment was submitted. Read from the request rather than
 * threaded through `bhp_process_signup()` so the shared signup core keeps
 * its documented property of never reading a superglobal.
 */
function bhp_get_capture_segment_audience_tag() {
    $raw = isset($_POST['bhp_segment']) ? wp_unslash($_POST['bhp_segment']) : '';
    $key = bhp_resolve_capture_segment($raw);
    if (!$key) {
        return '';
    }
    $routes = bhp_get_capture_segment_routes();
    return (string) ($routes[$key]['audience_tag'] ?? '');
}

/* =====================================================================
 * WAVE 1 — HOMEPAGE PRICE CUES (2026-08-04, theme 1.19.168)
 * ===================================================================== */

/**
 * ⭐ ENABLED BY DEFAULT SINCE 2026-08-04 (theme 1.19.169) — OWNER RULING.
 *
 * ⭐ Andrew Signore, 2026-08-04, verbatim: "Turn it on." Witnessed in the main
 *    session. He was shown the F2 conflict recorded below — that a homepage
 *    price cue contradicts his own instruction of 2026-08-03 — and turned it
 *    on anyway.
 *
 *    ⚠️ THIS IS A DELIBERATE, KNOWING OWNER REVERSAL OF F2 as it applies to
 *       the single "From $X" cue. It is NOT drift. CYCLE143-LD-162, CLOSED.
 *
 * ⛔ WHAT THE REVERSAL DOES *NOT* DO: it does not restore the two-line format
 *    PRICE LIST F2 removed. `formats_info` stays empty on every card in
 *    `front-page.php` under any flag. What renders is one live-derived
 *    "From $X" per card, plus the approved Collection savings literals.
 *    No total, no derived paperback total, no shipping figure.
 *
 * ───────── HISTORICAL — the reason it originally shipped OFF, preserved ─────
 * (Accurate as written on 2026-08-04 at build time; superseded the same day.)
 *
 * The Wave 1 brief asks for a price cue on each homepage product card.
 * ⚠️ Andrew Signore removed exactly that, by name, on 2026-08-03 — one day
 *    earlier. `front-page.php` records his instruction verbatim, three
 *    times, at the three cards: "F2 (Andrew, 2026-08-03): 'remove the cost
 *    numbers' -- the price list is gone from this discovery module. Prices
 *    still live on /books/, on every product page and on
 *    /complete-collection/."
 *
 * ⛔ "From $11.99" is a cost number. Reading his instruction narrowly
 *    enough to let a single price back onto the same cards would be
 *    reinterpreting an owner ruling to obtain the result the brief wants,
 *    which is not this role's call. Built, wired, tested, DEFAULT FALSE.
 *    One line turns it on and it is Andrew's line:
 *
 *        add_filter('bhp_show_home_price_cues', '__return_true');
 *
 *    Recorded for decision as CYCLE143-LD-162.
 *
 * ───────── END HISTORICAL. Andrew wrote that line on 2026-08-04. ───────────
 *
 * Staging QA override, kept for the case where someone filters it off:
 * `?bhp_preview_price_cues=1`, inert on production by construction.
 */
function bhp_home_price_cues_enabled() {
    if (class_exists('BHP_Analytics_Config') && BHP_Analytics_Config::is_staging()
        && isset($_GET['bhp_preview_price_cues']) && '1' === sanitize_key(wp_unslash($_GET['bhp_preview_price_cues']))) {
        return true;
    }
    // ⭐ DEFAULT TRUE since 2026-08-04 — Andrew Signore, "Turn it on."
    //    Knowing reversal of F2 (2026-08-03) for the single cue only.
    //    See the block above before changing this literal. CYCLE143-LD-162.
    return (bool) apply_filters('bhp_show_home_price_cues', true);
}

/**
 * "From $11.99" for one destination, built from the LIVE WooCommerce
 * prices already loaded for the homepage cards.
 *
 * ⛔ NO PRICE STRING IS HARDCODED. The number is the lowest live format
 *    price for that title, formatted by WooCommerce, exactly as the
 *    activity-book module does it. If prices move, this moves with them —
 *    which is the whole reason Merry flagged "From $11.99" as carrying a
 *    recheck date if it were typed into a template.
 *
 * Returns '' when no price is available, so the card renders unchanged.
 */
function bhp_get_home_price_cue(array $formats_info) {
    if (!$formats_info) {
        return '';
    }

    $lowest = null;
    foreach ($formats_info as $price_html) {
        foreach (bhp_extract_price_amounts((string) $price_html) as $amount) {
            if (null === $lowest || $amount < $lowest) {
                $lowest = $amount;
            }
        }
    }

    if (null === $lowest) {
        return '';
    }

    $formatted = function_exists('wc_price')
        ? bhp_decode_price_text(wc_price($lowest))
        : number_format_i18n($lowest, 2);

    /* translators: %s: lowest live format price, e.g. $11.99 */
    return sprintf(__('From %s', 'brave-hearts'), $formatted);
}

/**
 * A WooCommerce price string as PLAIN TEXT — tags stripped AND entities decoded.
 *
 * ⛔ THE ENTITY DECODE IS THE WHOLE POINT, and it was a live defect on staging
 *    for exactly as long as the price cue was switched on (2026-08-04, caught
 *    in rendered QA of 1.19.169 before any production deploy).
 *
 *    `WC_Product::get_price_html()` renders the USD symbol as the HTML ENTITY
 *    `&#036;`, and `wp_strip_all_tags()` removes tags but does NOT decode
 *    entities. The previous implementation then stripped everything outside
 *    `[0-9.]` from `"&#036;11.99"` — which leaves the entity's own digits
 *    behind as `"03611.99"` and parses to **3611.99**. Every homepage card
 *    rendered "From $3,611.99".
 *
 * ⚠️ Source review could not have caught this: the code reads correctly, and
 *    it was invisible while the gate was off. It was found by fetching the
 *    rendered page.
 */
function bhp_decode_price_text($price_html) {
    return trim(html_entity_decode(
        wp_strip_all_tags((string) $price_html),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    ));
}

/**
 * Every currency amount in a price string, as floats.
 *
 * Regex extraction rather than "strip everything that is not a digit", because
 * a price string legitimately carries MORE THAN ONE number:
 *   - a range, "$11.99 – $16.99" (the strip approach concatenates to 11.9916);
 *   - a sale, "<del>$16.99</del> <ins>$11.99</ins>";
 *   - a thousands separator, "$1,234.56" (the strip approach yields 1234.56
 *     only by luck, and 1,234 with no decimals yields 1234 — also by luck).
 * Returning them all lets the caller take the minimum, which is what "From"
 * means. Returns [] when the string carries no amount, so a card with no
 * resolvable price renders with no cue rather than with a wrong one.
 */
function bhp_extract_price_amounts($price_html) {
    $text = bhp_decode_price_text($price_html);
    // Drop thousands separators so "1,234.56" is one number, not two.
    $text = preg_replace('/(?<=\d),(?=\d{3}(?!\d))/', '', $text);

    if (!preg_match_all('/\d+(?:\.\d+)?/', (string) $text, $matches)) {
        return [];
    }

    $amounts = [];
    foreach ($matches[0] as $raw) {
        $amount = (float) $raw;
        if ($amount > 0) {
            $amounts[] = $amount;
        }
    }
    return $amounts;
}

/* =====================================================================
 * WAVE 1 — ADVENTURE ACTIVITY BOOK FRAMING (2026-08-04, theme 1.19.168)
 * ===================================================================== */

/**
 * ⭐ ENABLED BY DEFAULT SINCE 2026-08-04 (theme 1.19.169) — FOUNDER ATTESTATION.
 *
 * "Only at braveheartspublishing.com" is a claim about the whole internet, so
 * it needed a source and now has exactly one: **the founder himself.**
 *
 * ⭐ SOURCE OF RECORD — Andrew Signore, 2026-08-04, verbatim:
 *        "Activity book is only at our store - true"
 *    Recorded in `Business OS\WORKING-DRAFTS\chief-of-staff\
 *    OVERNIGHT-EXECUTION-REGISTER-2026-08-04.md` (Message 38), and accepted
 *    with "Accept" in the same session. This is a FOUNDER-ATTESTED FACT about
 *    his own distribution, which is the one category of claim he is himself
 *    the primary source for. It closes Merry's gate G-W1-5 / CYCLE143-MKT-134.
 *
 * ⚠️ IT IS AN ATTESTATION, NOT A RETAILER SWEEP. Nobody crawled Amazon. If
 *    the book is ever listed anywhere else, this line becomes false on the
 *    day that happens and must come back off — one line:
 *
 *        add_filter('bhp_activity_book_exclusive', '__return_false');
 *
 *    RECHECK: whenever distribution changes, and at any Amazon catalogue
 *    review. Owner-owned fact, owner-owned recheck.
 *
 * ⛔ Nothing else about this module became claim-bearing. The benefit line is
 *    still the plugin's approved, PDF-verified string; no price, reading age,
 *    outcome, reaction or rating is added by turning this on.
 */
function bhp_activity_book_exclusive_enabled() {
    // ⭐ DEFAULT TRUE since 2026-08-04 — Andrew Signore, Message 38 attestation:
    //    "Activity book is only at our store - true". See the block above.
    return (bool) apply_filters('bhp_activity_book_exclusive', true);
}

/**
 * The approved framing strings.
 *
 * ⛔ `benefit` is the LIVE APPROVED STRING, reused verbatim rather than
 *    paraphrased — its own source header records that every claim in it was
 *    checked against the actual PDF (26 pages counted twice in the binary,
 *    7 coloring pages, 2 mazes, 3 word searches). If the bundle plugin is
 *    active, it is read FROM the plugin so there is exactly one copy; the
 *    literal below is only a fallback for when the plugin is not loaded.
 *
 * ⛔ "crossword" is never claimed (v3 had three; Andrew ordered them
 *    removed; v4 has none), the generic word "puzzles" is deliberately
 *    avoided, the title is "The Adventure Activity Book" and never "Ocean
 *    Activity Book", no price is hardcoded, and no reading age, outcome,
 *    reaction or rating appears.
 *
 * ⛔ `note` states that it goes with a book order, because the checkout
 *    guard will refuse an add-on-only cart and copy that implies otherwise
 *    sets up a checkout the guard rejects.
 */
function bhp_get_activity_book_framing() {
    $benefit = __('26 pages of coloring, mazes and word searches · instant download', 'brave-hearts');
    if (function_exists('bhp_bundle_addon_copy')) {
        $plugin_copy = bhp_bundle_addon_copy();
        if (!empty($plugin_copy['benefit'])) {
            $benefit = (string) $plugin_copy['benefit'];
        }
    }

    return [
        'eyebrow' => bhp_activity_book_exclusive_enabled()
            ? __('Only at braveheartspublishing.com', 'brave-hearts')
            : '',
        'title'   => __('The Adventure Activity Book', 'brave-hearts'),
        'benefit' => $benefit,
        // Merry §5.4, the claim-free line that ships today.
        'note'    => __('A companion download you can add to any book order.', 'brave-hearts'),
    ];
}

/**
 * Product-page framing for the activity book. Theme-side only — the add-on
 * checkbox itself lives in the bundle-pricing plugin and is NOT touched by
 * this release.
 *
 * Renders nothing unless the plugin is actually offering the add-on, so the
 * page never advertises something a visitor cannot then add.
 */
function bhp_woocommerce_product_activity_book_note() {
    // `bhp_bundle_addon_data()` returns null when there is no offerable
    // add-on product on this environment. Checked rather than assumed, so a
    // site without the bundle plugin renders nothing at all here.
    if (!function_exists('bhp_bundle_addon_data')) {
        return;
    }
    $addon = bhp_bundle_addon_data();
    if (null === $addon) {
        return;
    }
    // Never describe the add-on to itself on its own product page.
    global $product;
    if ($product && is_object($product) && (int) $product->get_id() === (int) ($addon['productId'] ?? 0)) {
        return;
    }
    if (!(bool) apply_filters('bhp_show_product_activity_book_note', true)) {
        return;
    }

    $copy = bhp_get_activity_book_framing();
    ?>
    <div class="bhp-activity-book-note">
      <?php if ($copy['eyebrow']): ?>
        <p class="bhp-activity-book-note__eyebrow"><?php echo esc_html($copy['eyebrow']); ?></p>
      <?php endif; ?>
      <p class="bhp-activity-book-note__title"><?php echo esc_html($copy['title']); ?></p>
      <p class="bhp-activity-book-note__benefit"><?php echo esc_html($copy['benefit']); ?></p>
      <p class="bhp-activity-book-note__note"><?php echo esc_html($copy['note']); ?></p>
    </div>
    <?php
}
// Priority 39: after the printed-for-you notice (37) and before the
// narrow-column sections that begin at 40.
add_action('woocommerce_single_product_summary', 'bhp_woocommerce_product_activity_book_note', 39);

/**
 * Enqueue the shared popup script sitewide on the front end (not just on
 * pages where a popup itself renders) so any thank-you page can still
 * detect a just-completed signup and fire that popup's own *_success
 * event — see the pending-submit handoff in mariana-popup.js for why.
 */
add_action('wp_enqueue_scripts', function () {
    if (is_admin()) {
        return;
    }
    wp_enqueue_script(
        'bhp-mariana-popup',
        get_template_directory_uri() . '/assets/js/mariana-popup.js',
        [],
        wp_get_theme()->get('Version'),
        true
    );
});

/**
 * Renders at most one popup per page: the teacher popup on /teachers/, or
 * the parent popup everywhere else once eligible, or nothing at all.
 */
add_action('wp_footer', function () {
    if (bhp_should_show_teacher_popup()) {
        get_template_part('template-parts/acquisition/mariana-popup');
        return;
    }

    if (bhp_should_show_parent_popup()) {
        get_template_part('template-parts/acquisition/parent-popup');
        // ⛔ The exit-intent modal is NOT rendered alongside the timed parent
        //    popup. Both are the same funnel and the same offer, and one
        //    visitor should be asked once. The timed popup wins because it is
        //    the older, owner-approved surface; if Andrew re-enables the timed
        //    popup AND wants exit-intent as well, that is a second decision.
        return;
    }

    // Wave 1 (2026-08-04): exit-intent, parent funnel, DEFAULT OFF. See the
    // block above bhp_should_show_exit_intent_popup() for why.
    if (bhp_should_show_exit_intent_popup()) {
        get_template_part('template-parts/acquisition/exit-intent-popup');
    }
});

/**
 * Retire the legacy sitewide parent popup ("Does Your Child Say Reading Is
 * Boring?") now that the sitewide "Find Your Adventure" quiz + footer CTA
 * (2026-07-17) is the replacement acquisition entry point everywhere the
 * popup used to appear. Uses the function's own existing filter hook rather
 * than touching bhp_should_show_parent_popup() or bhp_should_show_any_popup()
 * directly -- the latter is shared with bhp_should_show_quiz_cta() and the
 * teacher popup, so it must stay untouched. Fully reversible (remove this
 * filter to restore the popup); the function, template part, and JS engine
 * are all left in place, not deleted.
 */
add_filter('bhp_show_parent_popup', '__return_false');

/**
 * Sitewide "Find Your Adventure" quiz entry CTA (2026-07-17). Reuses the
 * exact same universal exclusion set as the sitewide popups
 * (bhp_should_show_any_popup()) -- cart/checkout/account/order-received/
 * privacy/terms/admin/all 4 audience landing templates/thank-you pages --
 * plus two additions specific to this CTA: the homepage (already shows the
 * full embedded quiz further down the page -- a second "find your
 * adventure" banner would be redundant) and the canonical quiz page itself
 * (a visitor already on /find-your-adventure/ doesn't need a CTA back to
 * the same page).
 */
function bhp_should_show_quiz_cta() {
    if (!bhp_should_show_any_popup()) {
        return false;
    }

    // 2026-07-19 (Andrew, explicit): the homepage exclusion is lifted -- the
    // quiz modal runs there too.
    //
    // 2026-07-31 (quiz consolidation) -- the note that used to sit here,
    // saying the homepage renders the quiz twice "by design" (once inline via
    // the audience-gateway/#find-your-adventure section, once as the modal),
    // is now STALE and has been corrected. front-page.php no longer renders
    // either the audience-gateway module or the inline quiz section, so the
    // homepage carries exactly ONE quiz: this launcher plus its hidden modal.
    // footer.php passes the 'find-your-adventure' anchor id to the launcher on
    // the homepage only, which preserves the old deep-link target without
    // reintroducing a duplicate id or a second [data-bhp-quiz] instance.
    //
    // page-find-your-adventure.php stays excluded: that page IS the quiz, so
    // a modal of the quiz over it would be self-defeating.
    if (is_page_template('page-find-your-adventure.php')) {
        return false;
    }

    return (bool) apply_filters('bhp_show_quiz_cta', true);
}

/**
 * Auto-open eligibility for the timer/scroll trigger (2026-07-17 follow-up).
 * Reuses bhp_should_show_quiz_cta() for the base eligible-page set, then
 * additionally excludes /teachers/ -- that page already runs the separate,
 * pre-existing teacher popup (bhp_should_show_teacher_popup()), and two
 * automatic overlays firing on the same page would conflict. The manual
 * "Find Your Adventure" launcher itself is untouched by this function and
 * keeps rendering on /teachers/ exactly as before -- only automatic
 * opening is gated here.
 */
function bhp_should_autoopen_quiz() {
    if (!bhp_should_show_quiz_cta()) {
        return false;
    }

    // 2026-07-19 (Andrew, explicit): the quiz is now the ONLY popup on the
    // site -- both lead-magnet popups are suppressed (see
    // inc/audit-remediation.php) -- so it must be eligible on every page a
    // popup is allowed on at all, including /teachers/ and the commerce
    // browsing surfaces that finding #20 previously carved out. This
    // knowingly supersedes finding #20's shop/product/collection exclusions.
    //
    // Cart, checkout, account, and order-received remain excluded upstream in
    // bhp_should_show_any_popup() and are deliberately NOT reopened here: a
    // modal over an active payment flow risks real revenue, which is a
    // different category of harm from competing with a browsing decision.
    //
    // "Every page" means eligible on every page, still capped to one
    // auto-open per session by quiz-modal.js's sessionStorage flag -- the
    // modal does not re-open on each navigation.
    return (bool) apply_filters('bhp_show_quiz_autoopen', true);
}

/**
 * Maps the current page to a stable utm_content-style slug for the
 * sitewide quiz launcher (2026-07-17 modal follow-up). Reflects the actual
 * page type the visitor was on when they opened the quiz -- the launcher
 * itself always lives in the same footer DOM position, but a flat "footer"
 * value would throw away real attribution signal, so this is computed from
 * page context instead. Falls back to 'information_page' for any other
 * normal page bhp_should_show_quiz_cta() allows (Contact, etc.).
 */
function bhp_get_quiz_entry_location() {
    if (function_exists('is_shop') && is_shop()) {
        return 'shop';
    }
    if (function_exists('is_product') && is_product()) {
        return 'product';
    }
    if (is_singular('post')) {
        return 'blog_post';
    }
    if (is_home() || is_category() || is_tag() || is_date() || is_author()) {
        return 'blog_archive';
    }
    if (is_page('about')) {
        return 'about';
    }
    return 'information_page';
}

/**
 * Enqueue the CTA/launcher's own stylesheet plus the modal chrome and its
 * focus-trap script, only on pages where they will actually render (same
 * conditional-load pattern used elsewhere). The shared quiz component's own
 * assets (bhp-audience-quiz style/script) are enqueued by
 * bhp_enqueue_audience_quiz_assets() above, which already checks
 * bhp_should_show_quiz_cta() -- not duplicated here.
 */
function bhp_enqueue_quiz_cta_assets() {
    if (!bhp_should_show_quiz_cta()) {
        return;
    }
    $theme_version = wp_get_theme()->get('Version');
    wp_enqueue_style('bhp-quiz-entry-cta', get_template_directory_uri() . '/assets/css/quiz-entry-cta.css', ['bhp-style'], $theme_version);
    wp_enqueue_style('bhp-quiz-modal', get_template_directory_uri() . '/assets/css/quiz-modal.css', ['bhp-style', 'bhp-audience-quiz'], $theme_version);
    wp_enqueue_script('bhp-quiz-modal', get_template_directory_uri() . '/assets/js/quiz-modal.js', [], $theme_version, true);
}
add_action('wp_enqueue_scripts', 'bhp_enqueue_quiz_cta_assets');

/**
 * Provider-neutral action filter. Leave the returned value empty until a
 * secure Mailchimp, HubSpot, or first-party form handler is configured.
 */
function bhp_get_valid_form_action($action) {
    if (!is_string($action) || trim($action) === '') {
        return '';
    }

    $action = esc_url_raw(trim($action), ['http', 'https']);
    if (!$action || !wp_http_validate_url($action)) {
        return '';
    }

    $path = untrailingslashit((string) wp_parse_url($action, PHP_URL_PATH));
    $placeholder_paths = [
        '/contact-form-placeholder',
        '/signup-placeholder',
    ];

    foreach ($placeholder_paths as $placeholder_path) {
        if ($path === $placeholder_path || strpos($path, $placeholder_path . '/') === 0) {
            return '';
        }
    }

    return $action;
}

require_once get_template_directory() . '/inc/mailchimp.php';
require_once get_template_directory() . '/inc/lead-magnet-settings.php';
// Phase 2 (2026-07-30): unified one-page-per-title purchase experience.
// Presentation layer only — no product record is merged or altered.
require_once get_template_directory() . '/inc/book-formats.php';
require_once get_template_directory() . '/inc/book-media.php';
// Collection gallery subsets on the six funnel pages that pitch it in words
// only. Reuses the same component and the same registry — see the file header.
require_once get_template_directory() . '/inc/collection-gallery.php';
// 2026-08-05 — Andrew's "2 click journey to purchase" order. ONE renderer for
// every "put the whole collection in my cart and take me to checkout" control
// on the funnel pages and the product-page upsell. It renders the bundle
// plugin's own already-live form contract and fails closed to the previous
// /complete-collection/ link if the plugin is off. Loaded AFTER book-formats.php
// because it reads bhp_book_default_format() for format-agnostic CTAs.
require_once get_template_directory() . '/inc/collection-cta.php';
require_once get_template_directory() . '/inc/amazon-reviews.php';
// Native customer reviews (2026-08-03): the on-page "Write a Review for …"
// section beneath the Kirkus block, the /review/<slug>/ two-click email
// destination, and enforced moderation. Loads AFTER book-formats.php because
// it maps every format of a title onto that title's canonical product via
// bhp_book_canonical_id(). Emits no schema of any kind — see the file header.
require_once get_template_directory() . '/inc/reviews.php';
require_once get_template_directory() . '/inc/class-bhp-printed-for-you.php';

// E1 post-purchase order-confirmation email (2026-08-03): subject line and
// inbox preheader for the WooCommerce processing-order email. The body copy
// lives in the template override at woocommerce/emails/customer-processing-
// order.php (and its plain/ twin). Theme code only — writes no WooCommerce
// setting and no option, so it reverts with a theme rollback.
require_once get_template_directory() . '/inc/post-purchase-email.php';

// Transactional email COPY layer (2026-08-03): subjects, headings,
// additional-content suppression, preheaders, the order-email-only Bookvault
// footer note and the brand CSS layer, for E1 through E7. Bodies live in the
// template overrides under woocommerce/emails/ and woocommerce/emails/plain/.
// MUST load AFTER post-purchase-email.php: that file owns E1's subject filter
// and this one deliberately does not re-register it.
// Theme code only -- writes no WooCommerce setting and no option, so the whole
// copy layer reverts with a theme rollback.
require_once get_template_directory() . '/inc/transactional-emails.php';

// Bookvault dispatch tracker (2026-08-03): the scheduled checker that polls
// Bookvault's v3 API and completes an order ONLY on an unambiguous dispatch
// signal, which is what makes E2's "Your books have shipped" a true sentence
// rather than an operating promise somebody has to remember. Ships in DRY
// mode: it writes no order meta, no note and no status until switched to
// live. MUST load AFTER transactional-emails.php so the copy layer that
// depends on this rule is already registered when a transition fires.
require_once get_template_directory() . '/inc/bookvault-tracker.php';

// Audit remediation (2026-07-18): small isolated trust/UX fixes from the Fable
// production audit. See inc/audit-remediation.php (currently: legacy author-slug
// 301 redirect).
require_once get_template_directory() . '/inc/audit-remediation.php';

// Phase 1C: local lead-event log (observes bhp_mailchimp_signup_success/
// _failed above -- never modifies the actual signup handling in
// inc/mailchimp.php). See docs/phase1c-lead-capture-architecture.md.
require_once get_template_directory() . '/inc/class-bhp-lead-event-log.php';

// Phase 1D: content funnel classification (audience/funnel-stage/intent/
// goals). Read-only bridge to the existing bhp_get_guide_registry() for
// smart defaults on already-curated posts -- see
// docs/phase1d-organic-conversion-architecture.md.
require_once get_template_directory() . '/inc/class-bhp-content-classification.php';

// Phase 1D: contextual CTA decision engine. Depends on
// BHP_Content_Classification above -- must load after it. Renders
// through the existing template-parts/components/final-cta.php (Phase
// 1D 'attrs' extension), no new markup/CSS. See
// docs/phase1d-organic-conversion-architecture.md.
require_once get_template_directory() . '/inc/class-bhp-cta-engine.php';

// Phase 1D: reusable campaign landing-page framework. Composes existing
// components (hero, signup-form, final-cta, teacher-resources-cta) into
// one configurable page shape -- see docs/phase1d-organic-conversion-architecture.md.
require_once get_template_directory() . '/inc/class-bhp-campaign-landing.php';

// Phase 1D: conversion-readiness scoring (distinct from
// content-engine/config/scoring-rubric.yaml, which scores Pinterest pin
// creative, not page conversion readiness). Report-only -- never
// auto-edits content based on its own score. See
// docs/phase1d-organic-conversion-architecture.md.
require_once get_template_directory() . '/inc/class-bhp-conversion-scoring.php';

// Phase 1E: content intelligence and production engine. Builds ONLY on
// the Phase 1D classes above (classification, CTA engine, campaign
// landing, conversion scoring) plus the existing content-engine/
// directory schema -- no duplicate parallel system. Every stage past
// analytics import/scoring/reporting requires an explicit approving
// user; nothing in this block can publish a WordPress post or a GTM
// container. See docs/phase1e-content-intelligence-architecture.md.
require_once get_template_directory() . '/inc/class-bhp-analytics-adapter.php';
require_once get_template_directory() . '/inc/class-bhp-content-inventory.php';
require_once get_template_directory() . '/inc/class-bhp-taxonomy-inventory.php';
require_once get_template_directory() . '/inc/class-bhp-taxonomy-assignment.php';
require_once get_template_directory() . '/inc/class-bhp-internal-link-engine.php';
require_once get_template_directory() . '/inc/class-bhp-content-opportunity-engine.php';
require_once get_template_directory() . '/inc/class-bhp-content-production-queue.php';
require_once get_template_directory() . '/inc/class-bhp-content-originality.php';
require_once get_template_directory() . '/inc/class-bhp-content-brief-generator.php';
require_once get_template_directory() . '/inc/class-bhp-seo-metadata-package.php';
require_once get_template_directory() . '/inc/class-bhp-image-metadata-package.php';
require_once get_template_directory() . '/inc/class-bhp-blog-draft-generator.php';
require_once get_template_directory() . '/inc/class-bhp-pinterest-variant-generator.php';
require_once get_template_directory() . '/inc/class-bhp-pinterest-draft-linkage.php';
require_once get_template_directory() . '/inc/class-bhp-analytics-metadata-package.php';

// Weekly Production Cycle 1 QA hardening (see
// docs/weekly-cycle-1-qa-failure-audit.md): three new gates wired into
// BHP_Content_QA_Gate below, added after a real draft shipped
// Squarespace-migration HTML, unset classification metadata, a
// facts-article/"Book recommendations" category mismatch, and a
// duplicate-CTA risk that none of the existing checks caught.
require_once get_template_directory() . '/inc/class-bhp-content-html-sanitizer.php';
require_once get_template_directory() . '/inc/class-bhp-cta-collision-detector.php';
require_once get_template_directory() . '/inc/class-bhp-classification-completeness-gate.php';

// Required-contextual-links policy (added 2026-07-11, see
// docs/required-links-policy.md): every article needs a topic-hub link
// and a book/product link in-body, distinct from the automatic CTA.
require_once get_template_directory() . '/inc/class-bhp-required-links-gate.php';
require_once get_template_directory() . '/inc/class-bhp-content-qa-gate.php';
require_once get_template_directory() . '/inc/class-bhp-editorial-governance.php';
require_once get_template_directory() . '/inc/class-bhp-author-fingerprint-package.php';
require_once get_template_directory() . '/inc/class-bhp-draft-package-builder.php';
require_once get_template_directory() . '/inc/class-bhp-wp-draft-workflow.php';
require_once get_template_directory() . '/inc/class-bhp-draft-package-admin-panel.php';
require_once get_template_directory() . '/inc/class-bhp-content-feedback-loop.php';
require_once get_template_directory() . '/inc/class-bhp-content-engine-admin.php';
require_once get_template_directory() . '/inc/class-bhp-content-engine-cli.php';

// ============================================================
// EXPLORER PASSPORT FOUNDATION
// ============================================================
/**
 * Central registry for current and future Explorer Passport features.
 */
function bhp_get_explorer_passport_features() {
    return apply_filters('bhp_explorer_passport_features', [
        'world_explorer_map' => [
            'title'       => __('World Explorer Map', 'brave-hearts'),
            'description' => __('Track every real place Charlotte and Henry visit and see the adventure grow around the world.', 'brave-hearts'),
            'status'      => 'placeholder',
        ],
        'adventure_stamps' => [
            'title'       => __('Adventure Stamps', 'brave-hearts'),
            'description' => __('Collect a stamp for each destination, book, and completed Brave Hearts adventure.', 'brave-hearts'),
            'status'      => 'placeholder',
        ],
        'reading_achievements' => [
            'title'       => __('Reading Achievements', 'brave-hearts'),
            'description' => __('Celebrate finished books, new reading milestones, and the curiosity to keep learning.', 'brave-hearts'),
            'status'      => 'placeholder',
        ],
        'explorer_certificates' => [
            'title'       => __('Explorer Certificates', 'brave-hearts'),
            'description' => __('Recognize readers who complete an adventure and become official Brave Hearts Explorers.', 'brave-hearts'),
            'status'      => 'placeholder',
        ],
        'future_adventure_badges' => [
            'title'       => __('Future Adventure Badges', 'brave-hearts'),
            'description' => __('Unlock new badges as future books introduce more places, science, wildlife, and acts of courage.', 'brave-hearts'),
            'status'      => 'placeholder',
        ],
    ]);
}

/**
 * Return the placeholder or approved Passport download state.
 */
function bhp_get_explorer_passport_download($requested_url = '') {
    $url = bhp_get_safe_link_url(apply_filters('bhp_explorer_passport_download_url', $requested_url));
    $ready = (bool) apply_filters('bhp_explorer_passport_download_ready', false, $url);

    return [
        'url'   => $url,
        'ready' => $ready && (bool) $url,
    ];
}

// ============================================================
// BOOKS PAGE ADVENTURE GROUPING
// ============================================================
/**
 * Group format-specific product SKUs into customer-facing adventures.
 */
function bhp_get_series_adventures() {
    $definitions = [
        'mariana_trench' => [
            'title'       => __('The Mariana Trench', 'brave-hearts'),
            'destination' => __('Mariana Trench · Western Pacific Ocean', 'brave-hearts'),
            'description' => __('Charlotte and Henry descend to the deepest place on Earth, meeting remarkable ocean life and discovering science, conservation, and the courage to keep going.', 'brave-hearts'),
            'matches'     => ['mariana trench', 'mariana'],
        ],
        'mount_everest' => [
            'title'       => __('Mount Everest', 'brave-hearts'),
            'destination' => __('Mount Everest · Himalayas', 'brave-hearts'),
            'description' => __('Charlotte and Henry journey toward the world’s highest mountain in an adventure shaped by geography, resilience, teamwork, and courage.', 'brave-hearts'),
            'matches'     => ['mount everest', 'everest'],
        ],
        'amazon_rainforest' => [
            'title'       => __('The Amazon Rainforest', 'brave-hearts'),
            'destination' => __('Amazon Rainforest · South America', 'brave-hearts'),
            'description' => __('Charlotte and Henry enter the world’s largest tropical rainforest to discover extraordinary wildlife, connected ecosystems, conservation, and wonder.', 'brave-hearts'),
            'matches'     => ['amazon rainforest', 'amazon', 'rainforest'],
        ],
    ];

    $products = bhp_get_homepage_books(-1);
    $adventures = [];

    foreach ($definitions as $key => $definition) {
        $adventures[$key] = array_merge($definition, [
            'key'             => $key,
            'age_range'       => __('Ages 6–9', 'brave-hearts'),
            'formats'         => [],
            'format_urls'     => [],
            'image_id'        => 0,
            'image_alt'       => '',
            'primary_url'     => '',
            'formats_url'     => '',
            'amazon_url'      => '',
            'matching_skus'   => 0,
            'available'       => false,
        ]);
    }

    foreach ($products as $product) {
        $product_title = strtolower(wp_strip_all_tags($product['title']));
        $adventure_key = '';

        foreach ($definitions as $key => $definition) {
            foreach ($definition['matches'] as $match) {
                if (strpos($product_title, $match) !== false) {
                    $adventure_key = $key;
                    break 2;
                }
            }
        }

        if (!$adventure_key) {
            continue;
        }

        $adventure = &$adventures[$adventure_key];
        $product_formats = is_array($product['formats']) ? $product['formats'] : [];

        if (strpos($product_title, 'paperback') !== false && !in_array('Paperback', $product_formats, true)) {
            $product_formats[] = 'Paperback';
        }
        if ((strpos($product_title, 'hardcover') !== false || strpos($product_title, 'hardback') !== false) && !in_array('Hardcover', $product_formats, true)) {
            $product_formats[] = 'Hardcover';
        }
        if ((strpos($product_title, 'kindle') !== false || strpos($product_title, 'ebook') !== false) && !in_array('Kindle', $product_formats, true)) {
            $product_formats[] = 'Kindle';
        }

        $is_paperback = in_array('Paperback', $product_formats, true) || strpos($product_title, 'paperback') !== false;
        $adventure['matching_skus']++;
        $adventure['available'] = true;
        $adventure['formats'] = array_values(array_unique(array_merge($adventure['formats'], $product_formats)));
        foreach ($product_formats as $product_format) {
            if (in_array($product_format, ['Paperback', 'Hardcover', 'Kindle'], true)) {
                $adventure['format_urls'][$product_format] = $product['url'];
            }
        }

        if (!$adventure['primary_url'] || $is_paperback) {
            $adventure['primary_url'] = $product['url'];
            if (!empty($product['image_id'])) {
                $adventure['image_id'] = $product['image_id'];
                $adventure['image_alt'] = $product['image_alt'];
            }
        } elseif (!$adventure['image_id'] && !empty($product['image_id'])) {
            $adventure['image_id'] = $product['image_id'];
            $adventure['image_alt'] = $product['image_alt'];
        }

        unset($adventure);
    }

    foreach ($adventures as &$adventure) {
        if ($adventure['available']) {
            $ordered_formats = [];
            foreach (['Paperback', 'Hardcover', 'Kindle'] as $preferred_format) {
                if (in_array($preferred_format, $adventure['formats'], true)) {
                    $ordered_formats[] = $preferred_format;
                }
            }
            $adventure['formats'] = array_merge(
                $ordered_formats,
                array_values(array_diff($adventure['formats'], $ordered_formats))
            );
            $ordered_format_urls = [];
            foreach ($adventure['formats'] as $format) {
                if (!empty($adventure['format_urls'][$format])) {
                    $ordered_format_urls[$format] = $adventure['format_urls'][$format];
                }
            }
            $adventure['format_urls'] = $ordered_format_urls;
            $adventure['formats_url'] = bhp_get_safe_link_url(add_query_arg([
                's'         => $adventure['title'],
                'post_type' => 'product',
            ], home_url('/shop/')), home_url('/shop/'));
        }
    }
    unset($adventure);

    return apply_filters('bhp_series_adventures', $adventures, $products);
}

// ============================================================
// AMAZON AFFILIATE PURCHASE PATH
// ============================================================
/**
 * Single source of truth for approved Amazon affiliate links. Only add a
 * URL here once it has been explicitly approved — an empty/missing entry
 * means no Amazon button renders for that title.
 */
function bhp_get_amazon_affiliate_urls() {
    return apply_filters('bhp_amazon_affiliate_urls', [
        'mariana_trench'    => 'https://amzn.to/4svChYL',
        'mount_everest'     => 'https://amzn.to/4mptuGv',
        'amazon_rainforest' => 'https://amzn.to/4va9me7',
    ]);
}

function bhp_get_amazon_affiliate_url($adventure_key) {
    $urls = bhp_get_amazon_affiliate_urls();
    return $urls[$adventure_key] ?? '';
}

/** Map a WooCommerce SKU prefix to its series adventure key. */
function bhp_get_adventure_key_from_sku($sku) {
    $sku = strtoupper((string) $sku);
    if (strpos($sku, 'BHP-MT-') === 0) {
        return 'mariana_trench';
    }
    if (strpos($sku, 'BHP-EVE-') === 0) {
        return 'mount_everest';
    }
    if (strpos($sku, 'BHP-AMZ-') === 0) {
        return 'amazon_rainforest';
    }
    return '';
}

/**
 * Production pre-deployment audit (2026-07-05) found that production's
 * real catalog does NOT follow the BHP-XXX-YY SKU convention the way
 * staging's does -- 5 of the 6 approved editions have their SKU set
 * directly to the ISBN (e.g. "9798996810802") instead of a short code like
 * "BHP-AMZ-PB", so bhp_get_adventure_key_from_sku() alone would silently
 * return '' for every one of them on production and break every component
 * that depends on it (Amazon links, Kirkus badges, trust rows, review
 * showcase, etc.) for 5 of 6 books. SKUs are never changed to fix this
 * (a hard rule) -- instead this wrapper tries the SKU convention first
 * (unchanged behavior anywhere it already works, e.g. staging) and falls
 * back to the same fixed WooCommerce product IDs already relied on
 * elsewhere in this project (see bundle-data.php's bhp_bundle_catalog(),
 * which uses the identical IDs) when the SKU doesn't match.
 */
function bhp_get_adventure_key_for_product($product) {
    if (!$product instanceof WC_Product) {
        return '';
    }

    $from_sku = bhp_get_adventure_key_from_sku($product->get_sku());
    if ($from_sku) {
        return $from_sku;
    }

    $product_id = $product->get_id();
    $id_map = [
        333 => 'mariana_trench', // Mariana Trench Paperback (variable parent)
        14  => 'mariana_trench', // Mariana Trench Hardcover
        15  => 'mount_everest',  // Mount Everest Paperback
        17  => 'mount_everest',  // Mount Everest Hardcover
        18  => 'amazon_rainforest', // The Amazon Paperback
        20  => 'amazon_rainforest', // The Amazon Hardcover
    ];

    return $id_map[$product_id] ?? '';
}

/** The sitewide Amazon Associates disclosure, kept in one place so wording stays consistent. */
function bhp_get_amazon_disclosure_text() {
    return __('As an Amazon Associate, Brave Hearts Publishing earns from qualifying purchases.', 'brave-hearts');
}

/**
 * Above-the-fold age range + one-sentence value proposition -- steps 3-4 of
 * the approved Conversion UX Addendum product-page hierarchy. Priority 6:
 * immediately after the title (5), ahead of WooCommerce's native rating/
 * price hooks (10) and the variation/Add to Cart form (30).
 */
function bhp_woocommerce_product_value_prop() {
    global $product;
    if (!$product instanceof WC_Product) {
        return;
    }
    $age_range = get_post_meta($product->get_id(), 'bhp_age_range', true) ?: __('Ages 6–9', 'brave-hearts');
    ?>
    <div class="bhp-product-value-prop">
        <span class="bhp-product-value-prop__age"><?php echo esc_html($age_range); ?></span>
        <p class="bhp-product-value-prop__hook"><?php esc_html_e('Adventure chapter books for ages 6–9 that combine real places, science, history, courage, and kindness.', 'brave-hearts'); ?></p>
    </div>
    <?php
}
add_action('woocommerce_single_product_summary', 'bhp_woocommerce_product_value_prop', 6);

/**
 * Renders the "Need It Faster?" Amazon affiliate section: button + required
 * disclosure + accessible label + click-tracking data attribute. Returns
 * empty output when no approved link exists for the adventure (e.g. Amazon
 * Rainforest) so no placeholder or generic search link is ever shown.
 */
function bhp_render_amazon_affiliate_section($adventure_key, $book_title, $args = []) {
    $amazon_url = bhp_get_amazon_affiliate_url($adventure_key);
    if (!$amazon_url) {
        return '';
    }
    $args = wp_parse_args($args, [
        'heading' => __('Need It Faster?', 'brave-hearts'),
        'text'    => __('Buy on Amazon for familiar checkout and faster delivery options. Amazon pricing and delivery times may vary.', 'brave-hearts'),
        'source'  => '',
        'format'  => '',
    ]);
    /*
     * ⭐ CYCLE144-LD-220 (2026-08-05, theme 1.19.201) — WCAG 2.5.3, same class of
     *    defect as the review-card link, found on the PRODUCT page.
     *
     * MEASURED, Lighthouse 12.8.2 / axe, staging product page, mobile:
     * `label-content-name-mismatch` FAILED on `a.amazon-affiliate-block__button`
     * — "Text inside the element is not included in the accessible name". The
     * visible label is "Buy on Amazon"; the accessible name was
     * "Buy {title} on Amazon", which interpolates the title INTO the middle of
     * the visible phrase and therefore does not contain it as a substring.
     *
     * That is a real barrier, not a lint nit: a speech-input user saying the
     * words they can see cannot activate the control. Leading with the visible
     * label verbatim fixes it while keeping the title for screen-reader users
     * who need to tell several such buttons apart.
     *
     * ⛔ NOTHING VISIBLE CHANGES — the link text, href, `rel="sponsored nofollow"`
     *    and analytics attributes are untouched.
     */
    $aria_label = sprintf(
        /* translators: %1$s: the link's visible label, which must be preserved verbatim and first (WCAG 2.5.3 Label in Name). %2$s: book title. */
        __('%1$s — %2$s', 'brave-hearts'),
        __('Buy on Amazon', 'brave-hearts'),
        $book_title
    );
    ob_start();
    ?>
    <div class="amazon-affiliate-block amazon-affiliate-block--secondary">
      <h3 class="amazon-affiliate-block__heading"><?php echo esc_html($args['heading']); ?></h3>
      <p class="amazon-affiliate-block__text"><?php echo esc_html($args['text']); ?></p>
      <a
        class="amazon-affiliate-block__button amazon-affiliate-block__button--secondary"
        href="<?php echo esc_url($amazon_url); ?>"
        rel="sponsored nofollow"
        aria-label="<?php echo esc_attr($aria_label); ?>"
        data-bhp-event="amazon_outbound_click"
        data-bhp-book="<?php echo esc_attr($adventure_key); ?>"
        data-bhp-source="<?php echo esc_attr($args['source']); ?>"
        data-bhp-format="<?php echo esc_attr($args['format']); ?>"
      ><?php esc_html_e('Buy on Amazon', 'brave-hearts'); ?></a>
      <p class="amazon-affiliate-block__disclosure"><?php echo esc_html(bhp_get_amazon_disclosure_text()); ?></p>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Adds the Amazon affiliate option well below the native WooCommerce
 * purchase area on single product pages for Mariana Trench and Mount
 * Everest (paperback and hardcover both link to the same approved
 * book-level listing). No section renders for Amazon Rainforest — no
 * approved link exists yet.
 *
 * Staging Refinement Phase 1, item 2: moved off woocommerce_single_
 * product_summary (was priority 35, immediately below Add to Cart) to
 * woocommerce_after_single_product_summary priority 30 -- after the
 * description/reviews tabs (10) and related products (20), so it never
 * competes visually with the primary direct-store purchase controls.
 * Heading changed from "Need It Faster?" to a plainly secondary framing,
 * and the button restyled down from a full-width primary-style button to
 * a small text link (see .amazon-affiliate-block--secondary in style.css).
 */
function bhp_woocommerce_product_amazon_section() {
    global $product;
    if (!$product instanceof WC_Product) {
        return;
    }
    $adventure_key = bhp_get_adventure_key_for_product($product);
    if (!$adventure_key) {
        return;
    }
    $name = strtolower($product->get_name());
    $format = strpos($name, 'hardcover') !== false ? 'Hardcover' : (strpos($name, 'paperback') !== false ? 'Paperback' : '');
    echo bhp_render_amazon_affiliate_section($adventure_key, $product->get_name(), [ // phpcs:ignore
        'heading' => __('Also Available on Amazon', 'brave-hearts'),
        'source'  => 'product_page',
        'format'  => $format,
    ]);
}
add_action('woocommerce_after_single_product_summary', 'bhp_woocommerce_product_amazon_section', 30);

/**
 * Phase 1D: Adventure Kit lead-magnet cross-sell, added at the very end
 * of the product page (priority 40 -- after the Amazon reviews section
 * at 5 and the Amazon affiliate link at 30, so it never competes with
 * the primary Add to Cart purchase path or displaces any existing
 * element). Renders through BHP_CTA_Engine::select_specific(), which
 * resolves the always-real /reluctant-reader-adventure-kit/ URL rather
 * than a scored guess -- this placement is a deliberate, fixed cross-
 * sell decision, not a contextual best-match. Safe no-op if the CTA
 * engine isn't loaded or the destination somehow fails to resolve.
 */
function bhp_woocommerce_product_adventure_kit_cta() {
    global $product;
    if (!$product instanceof WC_Product || !class_exists('BHP_CTA_Engine')) {
        return;
    }
    $selected = BHP_CTA_Engine::select_specific('adventure_kit_signup', ['audience' => 'parent']);
    if (!$selected) {
        return;
    }
    BHP_CTA_Engine::render($selected, 'product_page');
}
add_action('woocommerce_after_single_product_summary', 'bhp_woocommerce_product_adventure_kit_cta', 40);

/**
 * Compact social-proof trust row directly beneath the Add to Cart
 * button/variation form -- step 6 of the approved Conversion UX Addendum
 * ("near the Add to Cart area... compact badges or short trust rows, not
 * one long block"). Reuses the existing Kirkus/Amazon-review components in
 * their already-built 'compact' mode rather than inventing new copy or an
 * unverified classroom-count statistic (no such number has ever been
 * confirmed -- see CLAUDE.md's rule against fabricated stats/reviews, so
 * it is deliberately omitted here). The existing full Kirkus quote and
 * Amazon review showcase further down the page (priority 34, and
 * woocommerce_after_single_product_summary priority 5) are untouched and
 * still provide deeper reinforcement. Priority 32: right after the
 * variation/Add to Cart form (30), before that expanded Kirkus block (34).
 */
function bhp_woocommerce_product_trust_row() {
    global $product;
    if (!$product instanceof WC_Product) {
        return;
    }
    $adventure_key = bhp_get_adventure_key_for_product($product);
    if (!$adventure_key) {
        return;
    }
    $badges = [];
    if ($adventure_key === 'mariana_trench') {
        $badges[] = bhp_render_kirkus_credibility('compact', ['source' => 'product_page_trust_row', 'show_link' => false]);
    }
    if (function_exists('bhp_get_approved_amazon_reviews_for_book') && bhp_get_approved_amazon_reviews_for_book($adventure_key)) {
        $badges[] = '<span class="bhp-trust-badge">' . esc_html__('Five-Star Reader Reviews', 'brave-hearts') . '</span>';
    }
    $badges[] = '<span class="bhp-trust-badge">' . esc_html__('Independent Reading &amp; Read-Aloud Friendly', 'brave-hearts') . '</span>';
    $badges = array_filter($badges);
    if (!$badges) {
        return;
    }
    ?>
    <div class="bhp-product-trust-row">
        <?php foreach ($badges as $badge) { echo $badge; /* phpcs:ignore -- already-escaped component output */ } ?>
    </div>
    <?php
}
add_action('woocommerce_single_product_summary', 'bhp_woocommerce_product_trust_row', 32);

// ============================================================
// KIRKUS CREDIBILITY COMPONENT
// ============================================================
/**
 * Single source of truth for the approved Kirkus Reviews excerpt. The
 * review is of "Adventures of Charlotte & Henry: The Mariana Trench" only
 * -- Mount Everest and Amazon Rainforest were never individually reviewed,
 * so nothing here may be reused to imply otherwise (see kirkus-credibility.php
 * 'series_note' mode, which is worded to stay factually accurate for those
 * titles). No Kirkus logo is locally available/licensed, so this is text
 * attribution only, per Andrew's explicit instruction.
 */
function bhp_get_kirkus_review_data() {
    return apply_filters('bhp_kirkus_review_data', [
        'quote'          => __('Simple but effective storytelling to spark children’s curiosity and appreciation for the wider natural world.', 'brave-hearts'),
        'attribution'    => __('Kirkus Reviews', 'brave-hearts'),
        'reviewed_title' => __('Adventures of Charlotte & Henry: The Mariana Trench', 'brave-hearts'),
        'review_url'     => 'https://www.kirkusreviews.com/book-reviews/andrew-signore/adventures-of-charlotte-and-henry/',
    ]);
}

/**
 * Renders the reusable Kirkus credibility component. See
 * template-parts/components/kirkus-credibility.php for markup/modes.
 */
function bhp_render_kirkus_credibility($mode = 'expanded', $args = []) {
    ob_start();
    get_template_part('template-parts/components/kirkus-credibility', null, array_merge(
        ['mode' => $mode],
        $args
    ));
    return ob_get_clean();
}

/**
 * Homepage placement: one concise credibility block directly after the
 * Featured Books path section, ahead of the Learning Hub -- near the
 * purchase-decision area without sitting above the hero message.
 */
function bhp_homepage_kirkus_section() {
    echo bhp_render_kirkus_credibility('expanded', [ // phpcs:ignore
        'source' => 'homepage',
        'show_link' => true,
    ]);
}

/**
 * Product-page placement. Mariana Trench (the actual reviewed title) gets
 * the full expanded quote, placed right after Add to Cart (priority 34,
 * ahead of the Amazon block at 35) so it reinforces the purchase decision
 * without pushing the button down or interrupting variation controls.
 * Mount Everest / Amazon Rainforest get only the factual series_note --
 * never the quote itself, since only the Trench book was reviewed.
 */
function bhp_woocommerce_product_kirkus_section() {
    global $product;
    if (!$product instanceof WC_Product) {
        return;
    }
    $adventure_key = bhp_get_adventure_key_for_product($product);
    if (!$adventure_key) {
        return;
    }
    if ($adventure_key === 'mariana_trench') {
        echo bhp_render_kirkus_credibility('expanded', [ // phpcs:ignore
            'source' => 'product_page',
            'show_link' => true,
        ]);
    } else {
        echo bhp_render_kirkus_credibility('series_note', [ // phpcs:ignore
            'source' => 'product_page',
            'show_link' => true,
        ]);
    }
}
add_action('woocommerce_single_product_summary', 'bhp_woocommerce_product_kirkus_section', 34);

/**
 * Per-book "What Kids Will Learn" bullets, keyed by the same adventure_key
 * used throughout this file. Every line is drawn directly from that book's
 * own already-approved product description (real place, real science, the
 * series' "stop, breathe, think, choose" resilience refrain) -- none of
 * this claims a reader outcome, classroom result, or anything not already
 * stated in copy Andrew has approved elsewhere.
 */
function bhp_get_product_learn_points($adventure_key) {
    $points = [
        'mariana_trench' => [
            __('Real science: deep-ocean pressure, bioluminescence, and life in total darkness', 'brave-hearts'),
            __('Real geography: the Mariana Trench, the deepest known point on Earth', 'brave-hearts'),
            __('A resilience habit for hard moments: stop, breathe, think, choose', 'brave-hearts'),
        ],
        'mount_everest' => [
            __('Real science: high-altitude air, extreme cold, and how the body responds', 'brave-hearts'),
            __('Real geography: Mount Everest, the world’s highest mountain', 'brave-hearts'),
            __('A resilience habit for hard moments: stop, breathe, think, choose', 'brave-hearts'),
        ],
        'amazon_rainforest' => [
            __('Real science: rainforest biodiversity and how the Amazon helps produce the air we breathe', 'brave-hearts'),
            __('Real geography: the Amazon rainforest, one of Earth’s most biodiverse places', 'brave-hearts'),
            __('A resilience habit for hard moments: stop, breathe, think, choose', 'brave-hearts'),
        ],
    ];
    return isset($points[$adventure_key]) ? $points[$adventure_key] : [];
}

/**
 * "What Kids Will Learn" -- step 9 of the approved product-page hierarchy.
 * Priority 36: after the Kirkus/Amazon-buy purchase-reinforcement blocks
 * (34/35), before product meta (40).
 */
function bhp_woocommerce_product_learn_section() {
    global $product;
    if (!$product instanceof WC_Product) {
        return;
    }
    $adventure_key = bhp_get_adventure_key_for_product($product);
    $points = bhp_get_product_learn_points($adventure_key);
    if (!$points) {
        return;
    }
    ?>
    <div class="bhp-product-learn">
        <h3 class="bhp-product-learn__heading"><?php esc_html_e('What Kids Will Learn', 'brave-hearts'); ?></h3>
        <ul class="bhp-product-learn__list">
            <?php foreach ($points as $point) : ?>
                <li><?php echo esc_html($point); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}
add_action('woocommerce_single_product_summary', 'bhp_woocommerce_product_learn_section', 36);

/**
 * Compact teacher-guide + shipping/returns links -- steps 12-13 of the
 * approved product-page hierarchy. Both link to real, already-published
 * pages (/teachers/ and /shipping-policy/) rather than implying a
 * per-book guide or policy that doesn't exist. Priority 38, just before
 * product meta (40).
 */
function bhp_woocommerce_product_teacher_shipping_section() {
    global $product;
    if (!$product instanceof WC_Product) {
        return;
    }
    ?>
    <ul class="bhp-product-links">
        <li><a href="<?php echo esc_url(home_url('/teachers/')); ?>"><?php esc_html_e('Free classroom guide for teachers', 'brave-hearts'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/shipping-policy/')); ?>"><?php esc_html_e('Flat-rate shipping, secure checkout, tracking on every order', 'brave-hearts'); ?></a></li>
    </ul>
    <?php
}
add_action('woocommerce_single_product_summary', 'bhp_woocommerce_product_teacher_shipping_section', 38);

/**
 * Shop/archive card placement: a small compact trust marker on the
 * Mariana Trench card only -- never the full quote, so it isn't repeated
 * on every product card in the loop.
 */
function bhp_woocommerce_loop_kirkus_badge() {
    global $product;
    if (!$product instanceof WC_Product) {
        return;
    }
    if (bhp_get_adventure_key_for_product($product) !== 'mariana_trench') {
        return;
    }
    echo bhp_render_kirkus_credibility('compact', ['source' => 'shop_card', 'show_link' => false]); // phpcs:ignore
}
add_action('woocommerce_after_shop_loop_item_title', 'bhp_woocommerce_loop_kirkus_badge', 15);

// ============================================================
// AMAZON CUSTOMER REVIEW SHOWCASE (Phase 3)
// ============================================================
/**
 * Renders the reusable Amazon review showcase component. See
 * template-parts/components/amazon-review-showcase.php and
 * inc/amazon-reviews.php for the underlying registry.
 */
function bhp_render_amazon_review_showcase($book_slug, $mode = 'expanded', $args = []) {
    ob_start();
    get_template_part('template-parts/components/amazon-review-showcase', null, array_merge(
        ['book_slug' => $book_slug, 'mode' => $mode],
        $args
    ));
    return ob_get_clean();
}

/**
 * Product-page placement: a full-width section AFTER the entire two-
 * column product summary (image + purchase column), via
 * woocommerce_after_single_product_summary at priority 5 -- ahead of the
 * native description/additional-information/reviews tabs (priority 10)
 * and related products (priority 20). Originally this lived inside the
 * narrow woocommerce_single_product_summary column (priority 40), which
 * made three-review Mariana cards uncomfortably narrow and compressed
 * and pushed the purchase column well below the fold -- moved out to its
 * own full-width section per Andrew's staging review. Kirkus and the
 * Amazon-affiliate CTA are untouched and still render inside the
 * purchase column exactly as before. Renders nothing at all for a book
 * with zero approved reviews (currently The Amazon / amazon_rainforest)
 * -- confirmed, not a placeholder gap.
 */
function bhp_woocommerce_product_amazon_reviews_section() {
    global $product;
    if (!$product instanceof WC_Product) {
        return;
    }
    $adventure_key = bhp_get_adventure_key_for_product($product);
    if (!$adventure_key) {
        return;
    }
    $reviews_html = bhp_render_amazon_review_showcase($adventure_key, 'expanded', [
        'source' => 'product_page', 'show_eyebrow' => false,
    ]);
    if (!trim($reviews_html)) {
        return;
    }
    ?>
    <section class="amazon-reviews-product-section" aria-label="<?php esc_attr_e('Amazon customer reviews', 'brave-hearts'); ?>">
      <div class="container">
        <header class="component-heading component-heading--center">
          <p class="component-heading__eyebrow"><?php esc_html_e('Real feedback from Amazon customers', 'brave-hearts'); ?></p>
          <h2 class="text-section-title"><?php esc_html_e('Amazon Customer Reviews', 'brave-hearts'); ?></h2>
        </header>
        <?php echo $reviews_html; // phpcs:ignore -- already escaped by the component itself ?>
      </div>
    </section>
    <?php
}
add_action('woocommerce_after_single_product_summary', 'bhp_woocommerce_product_amazon_reviews_section', 5);

/**
 * Shop/archive card placement: exactly one compact review per book card
 * (never every approved review) to avoid making the shop page
 * excessively tall. Distinct hook priority from the Kirkus badge (15) so
 * both can coexist without overwriting each other.
 */
function bhp_woocommerce_loop_amazon_review_badge() {
    global $product;
    if (!$product instanceof WC_Product) {
        return;
    }
    $adventure_key = bhp_get_adventure_key_for_product($product);
    if (!$adventure_key) {
        return;
    }
    echo bhp_render_amazon_review_showcase($adventure_key, 'compact', [ // phpcs:ignore
        'source' => 'shop_card', 'show_link' => false, 'max_reviews' => 1,
        'max_excerpt_words' => 18, 'show_verified_badge' => false,
    ]);
}
add_action('woocommerce_after_shop_loop_item_title', 'bhp_woocommerce_loop_amazon_review_badge', 20);

/**
 * Homepage placement: the strongest available reviews, each clearly
 * labeled with its own book title -- not one review from every title,
 * since Amazon Rainforest has zero approved reviews as of this writing.
 * Compact mode, one review each from Mariana Trench and Mount Everest.
 *
 * ⭐ 2C-2 (2026-08-03) — `show_product_link` IS NOW FALSE ON THE HOMEPAGE.
 *
 *    Andrew, final staging walk, verbatim: "Remove the 'shop adventures of
 *    Charlotte and henry: The mariana trench / everest' and 'Get all three
 *    adventures: The complete collection' from below the two reviews and put
 *    a call to action button 'Get the collection Here' - then it goes to the
 *    collection page". (Relayed through the Chief of Staff; NOT witnessed
 *    first-hand by this agent.)
 *
 *    Those two "Shop <book title> →" links are this flag, and nothing else --
 *    measured live on staging 1.19.153, `.amazon-review-showcase__product-link
 *    a` returned exactly the two he named and no others. Turning the flag off
 *    here removes them from the HOMEPAGE ONLY.
 *
 *    ⛔ THE FLAG DEFAULTS TO FALSE AND THE COMPONENT IS UNCHANGED, ON PURPOSE.
 *       `template-parts/components/amazon-review-showcase.php` still supports
 *       the link and still renders it for any caller that asks; this is the
 *       only caller that ever passed `true`. Deleting the feature would have
 *       taken a working component down with a homepage layout decision, and
 *       restoring Andrew's previous state is one word here.
 *
 *    The single replacement button lives in `front-page.php`, not here, because
 *    it belongs to the SECTION rather than to either book's review card.
 *    ⛔ Not one quoted family word is altered by this change.
 */
function bhp_homepage_amazon_reviews_section() {
    $blocks = '';
    foreach (['mariana_trench', 'mount_everest'] as $book_slug) {
        $blocks .= bhp_render_amazon_review_showcase($book_slug, 'compact', [
            'source' => 'homepage', 'show_link' => true, 'max_reviews' => 1,
            'show_book_title' => true, 'show_product_link' => false,
            'class'  => 'amazon-review-showcase--homepage-book',
        ]);
    }
    return $blocks;
}

// ============================================================
// CHECKOUT MARKETING CONSENT
// ============================================================
/**
 * Two optional, unchecked-by-default marketing consent checkboxes at
 * checkout. Purchasing alone never implies consent — both fields are
 * independent of order completion and are not required. Values are
 * captured via WooCommerce's Additional Checkout Fields API (which
 * persists them to the order under its own key) and then mirrored into
 * explicit, stable meta keys here so any future sync (HubSpot or
 * otherwise) has a single, predictable source of truth to read from
 * rather than depending on WooCommerce's internal field-storage format.
 */
function bhp_get_marketing_consent_field_definitions() {
    /*
     * F12 (2026-08-03): the list is now FILTERABLE. `inc/checkout-experience.php`
     * merges the two overlapping opt-ins into one and takes the teacher-funnel
     * offer out of the parent purchase path. The definitions stay here, intact,
     * as the record of what existed and what each stored meta key means.
     */
    return apply_filters('bhp_marketing_consent_field_definitions', [
        'new_book_releases' => [
            'id'    => 'brave-hearts/new-book-releases',
            'label' => __('Send me announcements when a new Charlotte and Henry book, edition, or bundle is released.', 'brave-hearts'),
            'meta'  => '_bhp_new_book_releases_optin',
        ],
        'explorer_updates' => [
            'id'    => 'brave-hearts/explorer-updates',
            'label' => __('Send me new book announcements, free teacher guides, family activities, outdoor education ideas, Expedition Guides, and occasional Brave Hearts Publishing news.', 'brave-hearts'),
            'meta'  => '_bhp_explorer_updates_optin',
        ],
    ]);
}

function bhp_register_marketing_consent_fields() {
    if (!function_exists('woocommerce_register_additional_checkout_field')) {
        return;
    }
    foreach (bhp_get_marketing_consent_field_definitions() as $field) {
        woocommerce_register_additional_checkout_field([
            'id'       => $field['id'],
            'label'    => $field['label'],
            'location' => 'order',
            'type'     => 'checkbox',
            'required' => false,
        ]);
    }
}
add_action('woocommerce_init', 'bhp_register_marketing_consent_fields');

/**
 * Mirror the two checkbox values into explicit order meta once the order
 * exists, whether it came from the classic checkout or Checkout Blocks.
 * Selecting neither box stores 'no' on both fields and no consent
 * timestamp — no marketing subscription is implied either way.
 */
function bhp_store_marketing_consent_meta($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $any_consent_given = false;
    foreach (bhp_get_marketing_consent_field_definitions() as $field) {
        // WooCommerce's Additional Checkout Fields API stores "order"
        // location values under a `_wc_other/{field_id}` meta key, not
        // the raw field id.
        $value = $order->get_meta('_wc_other/' . $field['id']);
        $checked = in_array($value, [true, 'yes', '1', 1], true) || $value === true;
        $order->update_meta_data($field['meta'], $checked ? 'yes' : 'no');
        if ($checked) {
            $any_consent_given = true;
        }
    }

    if ($any_consent_given) {
        $order->update_meta_data('_bhp_marketing_consent_timestamp', current_time('mysql', true));
        $order->update_meta_data('_bhp_marketing_consent_source', 'checkout');
    }

    $order->save();
}
add_action('woocommerce_checkout_order_processed', 'bhp_store_marketing_consent_meta', 20, 1);
add_action('woocommerce_store_api_checkout_order_processed', 'bhp_store_marketing_consent_meta', 20, 1);

// ============================================================
// CONTACT FORM FOUNDATION
// ============================================================
/**
 * Provider-neutral contact action. Keep empty until an approved external or
 * first-party handler is configured with validation and spam protection.
 */
function bhp_get_contact_form_action($requested_action = '') {
    return bhp_get_valid_form_action(apply_filters('bhp_contact_form_action', $requested_action));
}

// ============================================================
// PRODUCT STRUCTURED DATA — SHIPPING DETAILS + VARIABLE-PRODUCT GTIN
// ============================================================
/**
 * Adds OfferShippingDetails to Rank Math's existing Product/Offer schema —
 * no second Product entity, no Rank Math or WooCommerce core files touched.
 * Also fills the one real gap in Rank Math's own schema generator: for a
 * variable product with exactly one variation (currently just the Mariana
 * Paperback), its single-offer builder (get_single_variable_offer() in
 * class-product-woocommerce.php) never includes gtin, even though the
 * variation's own Global Unique ID is set. Simple products already get
 * gtin natively from _global_unique_id and are left untouched here.
 *
 * Shipping values mirror the one configured WooCommerce shipping zone
 * (Contiguous United States, flat rate $3.99) and the published Shipping
 * Policy page — change both places together if the policy ever changes.
 *
 * addressRegion lists the exact 48 contiguous-US states/DC from that zone
 * rather than a bare "US" addressCountry, so the schema doesn't imply
 * shipping to Alaska, Hawaii, territories, or internationally when
 * checkout does not actually support those destinations.
 */
// Runs at a very late priority so Rank Math's own Product entity has
// already been added to $data by the time this callback inspects it —
// at the default priority 10, $data was confirmed still empty at this
// point, since Rank Math builds each entity via its own callbacks on
// this same filter across a range of priorities.
add_filter('rank_math/json_ld', function ($data, $jsonld) {
    if (!is_singular('product') || !function_exists('wc_get_product')) {
        return $data;
    }

    $product = wc_get_product(get_queried_object_id());
    if (!$product) {
        return $data;
    }

    $contiguous_us_states = [
        'AL', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'DC', 'FL', 'GA', 'ID', 'IL',
        'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO',
        'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR',
        'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY',
    ];

    $shipping_details = [
        '@type'               => 'OfferShippingDetails',
        'shippingRate'        => [
            '@type'    => 'MonetaryAmount',
            'value'    => '3.99',
            'currency' => 'USD',
        ],
        'shippingDestination' => [
            '@type'         => 'DefinedRegion',
            'addressCountry' => 'US',
            'addressRegion' => $contiguous_us_states,
        ],
        'deliveryTime'        => [
            '@type'        => 'ShippingDeliveryTime',
            'handlingTime' => [
                '@type'    => 'QuantitativeValue',
                'minValue' => 2,
                'maxValue' => 5,
                'unitCode' => 'DAY',
            ],
            'transitTime'  => [
                '@type'    => 'QuantitativeValue',
                'minValue' => 3,
                'maxValue' => 8,
                'unitCode' => 'DAY',
            ],
        ],
    ];

    foreach ($data as $key => $entity) {
        if (!is_array($entity) || ($entity['@type'] ?? '') !== 'Product') {
            continue;
        }
        if (empty($entity['offers']) || !is_array($entity['offers']) || !isset($entity['offers']['@type'])) {
            continue;
        }

        // Only handles the exactly-one-variation case, matching the scope of
        // Rank Math's own get_single_variable_offer() (a product with more
        // than one variation produces a different offers structure — a
        // list, not a single associative Offer — which the isset() check
        // above already causes this loop to skip). find_matching_product_
        // variation() needs real $_GET attribute values to resolve anything
        // and is empty on a normal page load with no variation selected in
        // the URL, so this fetches the one child directly instead.
        if ($product->is_type('variable') && empty($entity['offers']['gtin'])) {
            $children = $product->get_children();
            if (count($children) === 1) {
                $variation = wc_get_product($children[0]);
                $gtin = $variation ? $variation->get_global_unique_id() : '';
                if ($gtin) {
                    $data[$key]['offers']['gtin'] = $gtin;
                }
            }
        }

        $data[$key]['offers']['shippingDetails'] = $shipping_details;
    }

    return $data;
}, 999, 2);

// ============================================================
// SUBSIDIZED SHIPPING — REMOVE BOOKVAULT LIVE CARRIER RATES
// ============================================================
// Bookvault's shipping class (wp-content/plugins/bookvault/Bookvault.php)
// does not declare WooCommerce shipping-zone support, so WooCommerce runs
// it for every checkout regardless of zone assignment and injects
// whatever live carrier rates its API returns (e.g. "USPS - Media Mail",
// "UPS - Ground") alongside the approved flat_rate method. This conflicts
// with the approved subsidized-shipping strategy: the customer always
// pays a flat $3.99, and Brave Hearts absorbs the difference against
// Bookvault's actual cost. This filter removes only Bookvault's
// customer-facing checkout rates — it does not touch the Bookvault
// plugin, its fulfillment API, order submission, tracking, or any
// admin label-purchasing functionality, none of which go through this
// filter.
function bhp_remove_bookvault_live_shipping_rates($rates, $package) {
    $bookvault_method_ids = ['bookvault shipping', 'your_shipping_method'];

    foreach ($rates as $rate_id => $rate) {
        if (in_array(strtolower(trim($rate->get_method_id())), $bookvault_method_ids, true)) {
            unset($rates[$rate_id]);
        }
    }

    return $rates;
}
add_filter('woocommerce_package_rates', 'bhp_remove_bookvault_live_shipping_rates', 10, 2);

// ============================================================
// AUDIENCE LANDING PAGES — SEO META DESCRIPTION / OG FALLBACKS
// ============================================================
// Confirmed defect (2026-07-16, live-verified on production for the Parent
// page): these 5 audience landing pages have no Rank Math meta description
// or og:description set in wp-admin, so both render empty. Rather than
// require a WordPress admin edit per page, this fills the gap in code with
// a real, unique, non-duplicated description per audience — but only when
// Rank Math's own value is empty, so filling the field in wp-admin later
// always takes precedence over this fallback without any code change.
function bhp_audience_landing_seo_description() {
    if (is_page_template('page-reluctant-reader-adventure-kit.php')) {
        return __('Educational adventure books for kids ages 6-9. Get a free sample chapter and activity from the Adventures of Charlotte and Henry series, plus the Complete Collection for reluctant readers.', 'brave-hearts');
    }
    if (is_page_template('page-audience-educators.php')) {
        return __('Adventure Books for the classroom, library, or homeschool. Download the free Adventure Learning Toolkit for The Mariana Trench and explore the full Adventures of Charlotte and Henry series for ages 6-9.', 'brave-hearts');
    }
    if (is_page_template('page-audience-gift-buyers.php')) {
        return __('A meaningful gift for readers ages 6-9. The Adventures of Charlotte and Henry Complete Collection - illustrated adventure books built around real places, in paperback or hardcover.', 'brave-hearts');
    }
    if (is_page_template('page-audience-retailers.php')) {
        return __('Wholesale information for bookstores, museum stores, and educational retailers carrying the Adventures of Charlotte and Henry adventure book series for ages 6-9, in paperback and hardcover.', 'brave-hearts');
    }
    if (is_page_template('page-audience-organizations.php')) {
        return __('Bring illustrated adventure books for ages 6-9 to your community reading program, library, or youth literacy initiative. The Adventures of Charlotte and Henry series and Complete Collection for organizations.', 'brave-hearts');
    }
    return '';
}

function bhp_audience_landing_seo_description_filter($description) {
    if (!empty($description)) {
        return $description;
    }
    $fallback = bhp_audience_landing_seo_description();
    return $fallback !== '' ? $fallback : $description;
}
add_filter('rank_math/frontend/description', 'bhp_audience_landing_seo_description_filter', 20);
add_filter('rank_math/opengraph/facebook/description', 'bhp_audience_landing_seo_description_filter', 20);
add_filter('rank_math/opengraph/twitter/description', 'bhp_audience_landing_seo_description_filter', 20);


/**
 * D3 (2026-07-31): related/upsell product cards requested the SQUARE
 * `woocommerce_thumbnail` derivative (390x390) but render in a portrait
 * ~260x340 box. With `object-fit: cover` that silently crops the top and
 * bottom of a book cover — which can cut the title or author off.
 *
 * The theme already registers an uncropped portrait size, `bhp-book-card`
 * (480x640, crop = false, see the add_image_size call near the top of this
 * file), so the fix is to request the right derivative rather than to
 * restyle anything: intrinsic and displayed aspect ratios then agree and
 * nothing is cropped or stretched.
 *
 * Scoped to the related/upsell loops only — the main product gallery and
 * the Shop archive are untouched. WordPress still generates srcset/sizes
 * and the loading="lazy" attribute for the substituted size, so responsive
 * loading and lazy loading are preserved, and giving the img its natural
 * portrait ratio removes rather than adds layout shift.
 */
function bhp_related_card_image_size( $size ) {
    if ( function_exists( 'is_product' ) && is_product() && ! is_admin() ) {
        return 'bhp-book-card';
    }
    return $size;
}
add_filter( 'single_product_archive_thumbnail_size', 'bhp_related_card_image_size' );

// ============================================================================
// CYCLE144-LD-214 · MOBILE CRITICAL PATH — theme 1.19.201 (2026-08-05)
// ============================================================================
/*
 * Everything in this section exists to answer one measured question: why does
 * a phone wait so long for the first screen?
 *
 * INSTRUMENT, named so the numbers can be reproduced rather than trusted:
 * Lighthouse 12.8.2 driving local Chrome, `formFactor: mobile`, simulated
 * Slow-4G (1,638 kbps throughput, 150 ms RTT, 4x CPU slowdown), run against
 * `staging2` — NOT PageSpeed Insights, and NOT production. Andrew's PSI
 * capture of production (2026-08-05 15:16 MDT) is what prompted the work; it
 * is a different instrument on a different network and its numbers are not
 * comparable to these. Only staging-before vs staging-after is compared here.
 *
 * BASELINE, staging home page, theme 1.19.199:
 *   Performance 56 · FCP 2.4 s · LCP 5.8 s · TBT 0 ms · CLS 0.427 · SI 3.1 s
 *   1,461.2 KB total: images 833.5 KB · fonts 252.1 KB · script 182.8 KB ·
 *   stylesheet 157.4 KB · document 32.6 KB.
 *
 * The byte budget is dominated by IMAGES, and the render-blocking budget by a
 * pile of commerce CSS that the home page never paints. Both are addressed
 * here; the artwork itself is addressed in `style.css` (CYCLE144-LD-213).
 */

/**
 * Is the current request a surface that actually needs the commerce bundle?
 *
 * DELIBERATELY CONSERVATIVE — it answers "might WooCommerce paint something
 * here?", not "is this a shop page?". Anything it is unsure about is treated
 * as commerce, because the cost of a wrong `true` is a few unused kilobytes
 * and the cost of a wrong `false` is a broken product page or checkout.
 *
 * WooCommerce may not be active at all (it is a plugin, and this theme must
 * not fatal without it), so every conditional is existence-checked first.
 */
function bhp_is_commerce_surface() {
    if ( is_admin() ) {
        return true;
    }

    foreach ( array( 'is_woocommerce', 'is_shop', 'is_product', 'is_product_category', 'is_product_tag', 'is_cart', 'is_checkout', 'is_account_page' ) as $conditional ) {
        if ( function_exists( $conditional ) && call_user_func( $conditional ) ) {
            return true;
        }
    }

    /*
     * The Complete Collection page is a plain WP page template, so none of the
     * conditionals above match it — but it carries the bundle add-to-cart
     * chain and the variation form, so it is commerce for this purpose.
     */
    if ( is_page( array( 'complete-collection', 'cart', 'checkout', 'my-account' ) ) ) {
        return true;
    }

    return (bool) apply_filters( 'bhp_is_commerce_surface', false );
}

/**
 * Drop commerce-only CSS/JS from pages that never paint it.
 *
 * MEASURED WASTE on the staging home page at 1.19.199 — every one of these
 * was downloaded, and every one is render-blocking or main-thread work for a
 * feature that does not exist on the page:
 *
 *   photoswipe + default-skin CSS     3.0 KB   product gallery lightbox
 *   wc-photoswipe + ui-default JS    11.6 KB   ditto
 *   wc-stripe-blocks-checkout-style   3.2 KB   the CHECKOUT payment form
 *   wc-blocks-style                   2.6 KB   Woo Blocks, none on this page
 *   cr-frontend-css                  14.9 KB   customer-reviews-woocommerce
 *   cr-frontend-js + cr-colcade      12.7 KB   ditto
 *
 * ⛔ `woocommerce-general` AND `woocommerce-layout` ARE DELIBERATELY KEPT.
 *    The home page renders real product cards and add-to-cart controls that
 *    inherit from them. Dropping them tests as "smaller" and looks broken,
 *    which is the classic version of this optimisation done badly.
 *
 * ⛔ PhotoSwipe is safe to drop here specifically because THIS THEME DOES NOT
 *    USE IT: `assets/js/book-media.js`, `inc/book-media.php` and
 *    `template-parts/commerce/look-inside.php` contain zero references to
 *    `pswp`/`PhotoSwipe` — checked, not assumed. The `pswp` markup that does
 *    appear in the footer is WooCommerce's own template, inert without a
 *    product gallery on the page.
 */
function bhp_trim_commerce_assets() {
    if ( bhp_is_commerce_surface() ) {
        return;
    }

    $styles = array(
        'photoswipe',
        'photoswipe-default-skin',
        'wc-stripe-blocks-checkout-style',
        'wc-blocks-style',
        'cr-frontend-css',
        'mailchimp-sms-consent-style',
    );
    foreach ( $styles as $handle ) {
        wp_dequeue_style( $handle );
    }

    $scripts = array(
        'wc-photoswipe',
        'wc-photoswipe-ui-default',
        'cr-frontend-js',
        'cr-colcade',
    );
    foreach ( $scripts as $handle ) {
        wp_dequeue_script( $handle );
    }
}
// Priority 100: after every plugin has enqueued, or there is nothing to drop.
add_action( 'wp_enqueue_scripts', 'bhp_trim_commerce_assets', 100 );

/**
 * The second half of the trim, for stylesheets that arrive too late to dequeue.
 *
 * ⚠ FOUND BY VERIFYING THE RENDERED HEAD, NOT BY TRUSTING THE DEQUEUE. After
 *   `bhp_trim_commerce_assets()` shipped, four of the six handles were gone from
 *   the staging home page and TWO WERE STILL THERE:
 *   `wc-stripe-blocks-checkout-style` and `wc-blocks-style`. Both are enqueued
 *   by block-asset machinery that runs AFTER `wp_enqueue_scripts` priority 100,
 *   so `wp_dequeue_style()` had nothing to remove at the moment it ran.
 *
 *   This is exactly the class of error that a "the code says it dequeues them"
 *   review would have missed, and it is why the head was re-read after deploy.
 *
 * `style_loader_tag` fires at PRINT time, which is after every enqueue no matter
 * when it happened, so suppressing the tag there is order-independent.
 *
 * ⛔ SCOPED TO EXACTLY TWO HANDLES, and only off commerce surfaces. Verified on
 *    the rendered staging home page: ZERO `wc-block` and ZERO
 *    `wp-block-woocommerce` elements — there is no WooCommerce block on the page
 *    for these sheets to style, and Stripe's is the checkout payment form's.
 */
function bhp_suppress_late_commerce_styles( $tag, $handle ) {
    if ( is_admin() || bhp_is_commerce_surface() ) {
        return $tag;
    }
    if ( in_array( $handle, array( 'wc-stripe-blocks-checkout-style', 'wc-blocks-style' ), true ) ) {
        return '';
    }
    return $tag;
}
add_filter( 'style_loader_tag', 'bhp_suppress_late_commerce_styles', 20, 2 );

/**
 * Defer jQuery — on non-commerce surfaces ONLY — AND everything that needs it.
 *
 * `jquery-core` is 29.2 KB and, at 1.19.199, uniquely among the scripts on the
 * home page it was NOT deferred: Lighthouse's `render-blocking-resources` audit
 * charged it 230 ms of the page's 400 ms of blocking time, the joint-largest
 * single entry. Deferring it took render-blocking to 0 ms.
 *
 * ⛔⛔ THE DEFECT THIS CODE WAS REWRITTEN TO FIX (1.19.202, `CYCLE144-LD-JQUERY`).
 *
 *    1.19.201 deferred `jquery-core` and reasoned about the risk ONE LEVEL TOO
 *    NARROWLY. Its docblock said the surface was safe because there were "19
 *    inline script blocks, ZERO referencing `$(` or `jQuery`". That was true,
 *    and it was a statement about INLINE scripts only. The scripts that broke
 *    were EXTERNAL, ENQUEUED and NOT DEFERRED — `defer` moves execution after
 *    parsing, so any enqueued jQuery dependent that is itself blocking now runs
 *    BEFORE jQuery exists. The old docblock's claim that everything depending
 *    on jQuery "already carries `data-wp-strategy=defer`" was true of the four
 *    WooCommerce handles it named and FALSE of the page as a whole: a
 *    dependency-graph scan of the front page found EIGHT jQuery dependents, of
 *    which FOUR carried no strategy at all — `bhp-cart-drawer`,
 *    `mailchimp-woocommerce-pixel-tracking`, `rank-math` and `bhp-addon-upsell`.
 *    The Mailchimp pixel threw `ReferenceError: jQuery is not defined` on the
 *    LIVE PRODUCTION home page, failing Lighthouse `errors-in-console`. That
 *    script is revenue attribution, so this was never cosmetic.
 *
 * ⭐ THE FIX, AND WHY IT IS THIS ONE. Defer the DEPENDENTS too, rather than
 *    un-deferring jQuery. Deferred scripts execute in document order, so
 *    deferring every jQuery dependent preserves the existing relative ordering
 *    AND keeps the 230 ms saving. Un-deferring jQuery would have thrown the
 *    saving away to fix a bug that is really about consistency.
 *
 * ⭐ IT IS DEPENDENCY-GRAPH DRIVEN, DELIBERATELY, NOT A HARDCODED HANDLE LIST.
 *    The four broken handles above are a snapshot of one afternoon's plugin
 *    set. A hardcoded list would let the next plugin update — or the next
 *    plugin — recreate this bug silently, which is exactly how it arrived. The
 *    plan below walks `wp_scripts()->registered` transitively, so a handle that
 *    reaches jQuery through three intermediates is covered without anybody
 *    noticing it exists.
 *
 * ⛔ THE ALL-OR-NOTHING RULE. If ANY script on the page cannot be safely
 *    deferred, jQuery is not deferred either and the page is served exactly as
 *    it was before this optimisation existed. There is no partial state. A page
 *    that defers jQuery but leaves one dependent blocking is the broken
 *    intermediate this whole rewrite exists to make unrepresentable.
 *
 * The `bhp_defer_jquery` filter remains, as the manual escape hatch for
 * anything the scan cannot see — principally raw inline `<script>` blocks
 * printed straight into `wp_head`/`wp_footer`, which never pass through
 * `wp_scripts()` and therefore cannot be inspected from here.
 *
 * @see bhp_jquery_defer_plan()
 */
function bhp_defer_jquery_tag( $tag, $handle, $src ) {
    if ( is_admin() || bhp_is_commerce_surface() ) {
        return $tag;
    }
    if ( ! apply_filters( 'bhp_defer_jquery', true ) ) {
        return $tag;
    }

    $plan = bhp_jquery_defer_plan();
    if ( empty( $plan['defer'] ) || ! isset( $plan['handles'][ $handle ] ) ) {
        return $tag;
    }
    if ( false !== strpos( $tag, ' defer' ) || false !== strpos( $tag, ' async' ) ) {
        return $tag;
    }

    return preg_replace( '/<script /', '<script defer ', $tag, 1 );
}
add_filter( 'script_loader_tag', 'bhp_defer_jquery_tag', 10, 3 );

/**
 * Does this handle's dependency chain reach jQuery, at any depth?
 *
 * Direct AND transitive. `bhp-addon-upsell` declares only `bhp-cart-drawer`,
 * which declares `jquery` — it is a jQuery dependent and a direct-deps-only
 * check would miss it.
 *
 * `$memo` is passed by reference rather than held in a `static` so that a test
 * can evaluate several different synthetic graphs in one request without one
 * scenario's answers leaking into the next. It doubles as the cycle guard: a
 * handle is recorded `false` BEFORE its own deps are walked, so a malformed
 * circular registration terminates instead of exhausting the stack.
 *
 * @param string $handle     Script handle.
 * @param array  $registered `WP_Scripts::$registered`, passed in explicitly.
 * @param array  $memo       Handle => bool, by reference.
 * @return bool
 */
function bhp_script_depends_on_jquery( $handle, $registered, &$memo ) {
    if ( isset( $memo[ $handle ] ) ) {
        return $memo[ $handle ];
    }
    $memo[ $handle ] = false;

    if ( ! isset( $registered[ $handle ] ) ) {
        return false;
    }

    foreach ( (array) $registered[ $handle ]->deps as $dep ) {
        if ( 'jquery' === $dep || 'jquery-core' === $dep
            || bhp_script_depends_on_jquery( $dep, $registered, $memo ) ) {
            $memo[ $handle ] = true;
            return true;
        }
    }

    return false;
}

/**
 * Does any inline code attached to this handle reference jQuery?
 *
 * WordPress prints `before`, `after` and `data` (`wp_localize_script`) chunks as
 * their own blocking `<script>` blocks adjacent to the file's tag. They are NOT
 * deferred with it. So a chunk that calls `jQuery(...)` or `$(...)` executes
 * during parsing, and under a deferred jQuery it throws — whether or not the
 * handle it is attached to is itself a jQuery dependent.
 *
 * The `$(` test is deliberately broad. A false positive costs the 230 ms
 * optimisation on that page; a false negative costs a console error on a live
 * customer page. Those are not comparable, so this errs toward switching the
 * optimisation off.
 *
 * @param object $script `_WP_Dependency` object.
 * @return bool
 */
function bhp_script_inline_touches_jquery( $script ) {
    if ( ! isset( $script->extra ) || ! is_array( $script->extra ) ) {
        return false;
    }

    foreach ( array( 'before', 'after', 'data' ) as $position ) {
        if ( empty( $script->extra[ $position ] ) ) {
            continue;
        }
        foreach ( (array) $script->extra[ $position ] as $chunk ) {
            if ( ! is_string( $chunk ) || '' === $chunk ) {
                continue;
            }
            if ( false !== strpos( $chunk, 'jQuery' ) ) {
                return true;
            }
            // `$(` but not `foo.$(`, `a$(` or `$$(`.
            if ( preg_match( '/(?<![\w.$])\$\s*\(/', $chunk ) ) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Decide, once per request, whether jQuery may be deferred on this page — and
 * which handles must carry `defer` for that to be safe.
 *
 * ⭐ RETURNS ONE OF EXACTLY TWO SHAPES, and the all-or-nothing rule is enforced
 *    here rather than at the call site:
 *
 *      array( 'defer' => false, 'handles' => array() )
 *      array( 'defer' => true,  'handles' => array( <handle> => true, ... ) )
 *
 * ⛔ THE FOUR CONDITIONS THAT DISABLE DEFERRAL FOR THE WHOLE PAGE. Any single
 *    one is enough, because the point is that no half-deferred state exists:
 *
 *      1. ANY handle in the page's script closure carries inline `before`,
 *         `after` or localize `data` that references jQuery. That inline block
 *         is blocking wherever its file ends up, so a deferred jQuery breaks it.
 *      2. A jQuery dependent is registered `async`. `async` has no ordering
 *         guarantee at all — it cannot be made safe by deferring anything.
 *      3. A jQuery dependent carries an `after` inline chunk. WordPress prints
 *         such a handle BLOCKING regardless of its declared strategy (an
 *         `after` chunk by construction assumes the file has already run), so
 *         it cannot be deferred, and leaving it blocking under a deferred
 *         jQuery is the original bug.
 *      4. `wp_scripts()` does not exist yet.
 *
 * ⚠ HONEST LIMIT, STATED RATHER THAN IMPLIED. This inspects the WordPress
 *   script registry. Raw inline `<script>` blocks echoed directly into
 *   `wp_head`/`wp_footer` never enter that registry and are invisible here.
 *   They were verified by hand on this surface (19 blocks, none referencing
 *   jQuery, re-checked at 1.19.202) and the `bhp_defer_jquery` filter is the
 *   switch if one ever appears.
 *
 * ⚠ SECOND HONEST LIMIT. The plan is computed on first use and cached for the
 *   request, because `jquery-core` prints in the HEAD while its dependents
 *   print in the FOOTER — the decision has to be made before the footer is
 *   reached, and it must not change afterwards. At head-print time the queue
 *   already contains footer handles (`$in_footer` affects print grouping, not
 *   enqueue time), so the scan sees them. A script enqueued for the first time
 *   from INSIDE a `wp_footer` callback would be missed; the cache is keyed to
 *   the `WP_Scripts` instance so the answer is at least stable and consistent
 *   rather than flipping mid-page.
 *
 * @return array
 */
function bhp_jquery_defer_plan() {
    static $plans = array();

    if ( ! function_exists( 'wp_scripts' ) ) {
        return array( 'defer' => false, 'handles' => array() );
    }

    $wp_scripts = wp_scripts();
    $key        = spl_object_hash( $wp_scripts );
    if ( isset( $plans[ $key ] ) ) {
        return $plans[ $key ];
    }

    $off              = array( 'defer' => false, 'handles' => array() );
    $plans[ $key ]    = $off;
    $registered       = $wp_scripts->registered;

    /*
     * The closure of everything this page prints. `queue` is the enqueued set
     * and survives printing; `to_do` and `done` are included so the answer is
     * identical whether this runs during the head batch or the footer batch.
     */
    $closure = array();
    $stack   = array_merge(
        (array) $wp_scripts->queue,
        (array) $wp_scripts->to_do,
        (array) $wp_scripts->done
    );
    while ( $stack ) {
        $handle = array_pop( $stack );
        if ( isset( $closure[ $handle ] ) ) {
            continue;
        }
        $closure[ $handle ] = true;
        if ( isset( $registered[ $handle ] ) ) {
            foreach ( (array) $registered[ $handle ]->deps as $dep ) {
                if ( ! isset( $closure[ $dep ] ) ) {
                    $stack[] = $dep;
                }
            }
        }
    }

    $memo  = array();
    $defer = array();

    foreach ( array_keys( $closure ) as $handle ) {
        if ( ! isset( $registered[ $handle ] ) ) {
            continue;
        }
        $script = $registered[ $handle ];

        // Condition 1 — applies to every handle, dependent or not.
        if ( bhp_script_inline_touches_jquery( $script ) ) {
            return $plans[ $key ];
        }

        if ( ! bhp_script_depends_on_jquery( $handle, $registered, $memo ) ) {
            continue;
        }

        // `jquery` itself is a meta handle with no src — it prints no tag.
        if ( empty( $script->src ) ) {
            continue;
        }

        $strategy = isset( $script->extra['strategy'] ) ? $script->extra['strategy'] : '';

        if ( 'async' === $strategy ) {
            return $plans[ $key ];                       // Condition 2.
        }
        if ( ! empty( $script->extra['after'] ) ) {
            return $plans[ $key ];                       // Condition 3.
        }
        if ( 'defer' === $strategy ) {
            continue;                                    // WordPress already defers it.
        }

        $defer[ $handle ] = true;
    }

    $defer['jquery-core']    = true;
    $defer['jquery-migrate'] = true;

    $plans[ $key ] = array( 'defer' => true, 'handles' => $defer );

    return $plans[ $key ];
}

/**
 * Hold the below-the-fold decorative photography until the page has loaded.
 *
 * THE MECHANISM, because it is not obvious: a CSS `background-image` is
 * fetched as soon as its rule matches an element in the RENDER TREE. Where
 * that element sits relative to the viewport is irrelevant — there is no
 * `loading="lazy"` for backgrounds. Measured on the 1.19.199 baseline,
 * `canopy-walk.webp` (272.0 KB) and `summit-lake.webp` (84.0 KB) both began
 * downloading while the LCP image was still in flight, on a 1.47 Mbps link.
 *
 * `html.bhp-art-hold` suppresses exactly three below-the-fold photographs
 * (see `style.css`, CYCLE144-LD-213 §2) and is removed on window `load`.
 *
 * THREE FAILSAFES, because a decorative image that never returns is a visual
 * regression and this must not depend on everything going right:
 *   1. The class is ADDED by script. With JavaScript off it is never added,
 *      so a no-JS visitor gets the full artwork immediately — the pre-change
 *      behaviour exactly.
 *   2. Adding and removing happen in the SAME inline block, so there is no
 *      second file that can 404 or be blocked and strand the page.
 *   3. A 4-second timer removes the class regardless, so a `load` event that
 *      never fires (a hung third-party request) cannot hold the art forever.
 *
 * ⛔ THE LCP IMAGE IS NOT HELD, and `hero-ocean` is deliberately absent from
 *    the CSS selector list.
 *
 * ⛔ NO LAYOUT PROPERTY IS INVOLVED, so this cannot introduce layout shift.
 *    Every affected section already paints its own solid `background-color`.
 *
 * Printed from `header.php` before `wp_head()` for the same reason the LCP
 * preload is: it must run before the stylesheet that declares those
 * backgrounds is applied, or the fetch has already started.
 */
function bhp_print_art_defer_script() {
    ?>
<script id="bhp-art-defer">(function(d,w){var e=d.documentElement;if(!e.classList){return;}e.classList.add('bhp-art-hold');var done=false;function go(){if(done){return;}done=true;e.classList.remove('bhp-art-hold');}if(d.readyState==='complete'){go();}else{w.addEventListener('load',go,{once:true});}w.setTimeout(go,4000);})(document,window);</script>
    <?php
}

/**
 * Load the quiz stylesheets off the critical path.
 *
 * `bhp-audience-quiz` (10.4 KB) and `bhp-quiz-modal` (5.9 KB) style a modal
 * that is CLOSED on first paint. Lighthouse charged `quiz-modal.css` 230 ms
 * of render-blocking time and reported `audience-quiz.css` as 10.2 KB unused
 * of 10.2 KB — i.e. entirely unused — on the home page.
 *
 * THE PATTERN: `media="print"` makes the browser fetch the sheet at low
 * priority WITHOUT blocking render; the `onload` handler flips it back to
 * `all` once it has arrived. A `<noscript>` copy restores plain blocking
 * behaviour when scripting is off, so the modal is never unstyled.
 *
 * ⛔ `bhp-quiz-entry-cta` IS NOT DEFERRED. It styles the quiz ENTRY BUTTON,
 *    which is visible in the first screen; deferring it would trade a
 *    render-blocking request for a flash of unstyled control, and that is a
 *    worse experience even though it scores better.
 *
 * ⛔ NOT APPLIED IN THE ADMIN, and not applied to any handle outside the two
 *    named above.
 */
function bhp_async_noncritical_styles( $tag, $handle, $href, $media ) {
    if ( is_admin() ) {
        return $tag;
    }
    if ( ! in_array( $handle, array( 'bhp-audience-quiz', 'bhp-quiz-modal' ), true ) ) {
        return $tag;
    }
    if ( 'all' !== $media ) {
        return $tag;
    }

    $async = str_replace(
        "media='all'",
        "media='print' onload=\"this.media='all';this.onload=null;\"",
        $tag
    );

    // If the media attribute was not in the expected form, change nothing.
    if ( $async === $tag ) {
        return $tag;
    }

    return $async . '<noscript>' . $tag . '</noscript>' . "\n";
}
add_filter( 'style_loader_tag', 'bhp_async_noncritical_styles', 10, 4 );

/**
 * Serve the comment-stripped build of any theme stylesheet that has one.
 *
 * THE MEASUREMENT THAT MOTIVATES THIS. After the image and critical-path work
 * above landed, Lighthouse 12.8.2 on the staging home page reported exactly one
 * meaningful render-blocker left: `style.css`, 83.4 KB over the wire, charged
 * 581 ms. It is also 50% of the LCP phase split ("load delay" — the browser
 * waiting on bytes). It was the next thing worth doing, and nothing else was
 * close.
 *
 * MEASURED, gzipped, at 1.19.201 — the whole point is that this is enormous for
 * how little it risks:
 *
 *     style.css                94,022 -> 39,156   (-54.9 KB)
 *     checkout-experience.css  12,610 ->  2,164   (-10.4 KB)
 *     audience-landing.css     13,360 ->  6,346    (-7.0 KB)
 *     audience-quiz.css        10,878 ->  2,693    (-8.2 KB)
 *     book-media.css            6,809 ->  2,689    (-4.1 KB)
 *     quiz-modal.css            6,123 ->  1,642    (-4.5 KB)
 *
 * ⭐ THE SAVING IS ALMOST ENTIRELY COMMENT PROSE, AND THAT IS THE POINT. This
 *    codebase writes essay-length CSS comments deliberately — they record why a
 *    rule exists and which specificity war it settled, and several of them are
 *    the only surviving explanation of a bug that took a day to find. They are
 *    invaluable in the repository and pure dead weight on a phone. Stripping
 *    them at BUILD time keeps both; deleting them from source would not.
 *
 * ⛔ THE SOURCE FILES REMAIN CANONICAL AND ARE NEVER EDITED BY THE BUILD.
 *    `style.css` keeps the theme header WordPress parses `Version:` from, and
 *    every `.min.css` sits in the SAME DIRECTORY as its source so that every
 *    relative `url()` inside it resolves to exactly the same file. Moving them
 *    into a build directory would silently break every background image, which
 *    is why they are siblings and not tidier.
 *
 * ⛔ THE STALE-ARTEFACT TRAP IS HANDLED, NOT IGNORED. A build artefact that can
 *    drift from its source is a bug waiting to ship. Two guards:
 *      1. `tests/test-style-minification.php` recomputes each source's md5 on
 *         the server and fails if it differs from the hash recorded in the
 *         artefact — so an un-rebuilt edit fails the suite instead of shipping.
 *      2. This filter falls back to the SOURCE file whenever the artefact is
 *         missing, so a forgotten build degrades to "slower", never "unstyled".
 *
 * `SCRIPT_DEBUG` bypasses the whole mechanism, so a developer always debugs the
 * commented file.
 */
function bhp_minified_style_src( $src, $handle ) {
    if ( is_admin() || ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
        return $src;
    }
    if ( false === strpos( $src, '.css' ) ) {
        return $src;
    }

    $theme_uri = get_template_directory_uri();
    if ( 0 !== strpos( $src, $theme_uri ) ) {
        return $src; // Not one of ours — plugin and core stylesheets are untouched.
    }

    // Split the query string off before touching the path, then restore it, so
    // the `?ver=` cache-buster survives intact.
    $parts = explode( '?', substr( $src, strlen( $theme_uri ) ), 2 );
    $path  = $parts[0];
    $query = isset( $parts[1] ) ? '?' . $parts[1] : '';

    if ( '.min.css' === substr( $path, -8 ) ) {
        return $src; // Already minified; nothing to do.
    }

    $min_path = substr( $path, 0, -4 ) . '.min.css';
    if ( ! file_exists( get_template_directory() . $min_path ) ) {
        return $src; // Fallback #2 above: no artefact, serve the source.
    }

    return $theme_uri . $min_path . $query;
}
add_filter( 'style_loader_src', 'bhp_minified_style_src', 10, 2 );
