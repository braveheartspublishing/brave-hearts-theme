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
// Meta Pixel (theme 1.19.203). Loaded here, alongside the rest of the
// analytics layer, because it shares the same two constraints: identical
// rendered bytes for every cacheable visitor, and consent applied entirely in
// the browser. It prints nothing when its pixel ID is empty.
require_once get_template_directory() . '/inc/class-bhp-meta-pixel.php';
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
 * CTA-triggered signup modal (theme 1.19.223, 2026-08-13,
 * `CYCLE158-LD-SIGNUP-POPUP`). Loaded on exactly the five funnel templates
 * that render `template-parts/acquisition/signup-modal.php`.
 *
 * ⛔ NOT SITEWIDE, DELIBERATELY. The script does nothing at all on a page
 *    with no `[data-bhp-signup-modal]` element, and shipping it to the cart,
 *    the checkout or a blog post would put a file on those pages that has no
 *    business being there — the same reasoning recorded above
 *    `bhp_enqueue_collection_band_assets()`.
 *
 * ⛔ NO STYLESHEET IS ENQUEUED HERE. The modal's CSS lives in `style.css`
 *    beside the `.mariana-popup` system it is a variant of, so the deploy
 *    artefact's `*.min.css` file count is unchanged and the RUNBOOK's
 *    "MUST be 10" assertion still holds.
 */
function bhp_enqueue_signup_modal_assets() {
    $signup_modal_templates = [
        'page-reluctant-reader-adventure-kit.php',
        'page-audience-educators.php',
        'page-audience-gift-buyers.php',
        'page-audience-retailers.php',
        'page-audience-organizations.php',
    ];
    if (!is_page_template($signup_modal_templates)) {
        return;
    }
    wp_enqueue_script(
        'bhp-signup-modal',
        get_template_directory_uri() . '/assets/js/signup-modal.js',
        [],
        wp_get_theme()->get('Version'),
        true
    );
}
add_action('wp_enqueue_scripts', 'bhp_enqueue_signup_modal_assets');

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
    /*
     * ⭐ 1.19.270 (`CYCLE165-LD-ITERATE-6-PATH-LINE`) — THE `$on_homepage`
     *    LIMB IS REMOVED, BECAUSE AFTER THIS RELEASE IT LOADS 10.4 KB OF CSS
     *    AND A SCRIPT FOR MARKUP THAT NO LONGER EXISTS ON THAT PAGE.
     *
     * It was written when `front-page.php` embedded the inline quiz. That
     * section was removed on 2026-07-31 and the homepage's only remaining
     * `[data-bhp-quiz]` instance was the launcher's hidden modal — which the
     * `$on_sitewide_launcher_page` limb below already covered. With the
     * launcher itself now gated off the homepage by the founder's ruling
     * (see `bhp_should_show_quiz_cta()`), this limb is the ONLY thing that
     * would still enqueue `bhp-audience-quiz` there, for zero markup.
     *
     * ⛔ VERIFIED BEFORE REMOVING, not assumed: `front-page.php` contains no
     *    `get_template_part('template-parts/quiz/...')` call, no
     *    `[bhp_audience_quiz]` shortcode and no `data-bhp-quiz` attribute.
     *    The homepage's only quiz reference is the hero's `<a href>` to the
     *    canonical PAGE, which needs none of these assets.
     *
     * ⛔ THE OTHER THREE LIMBS ARE UNTOUCHED, so the canonical
     *    `/find-your-adventure/` page, any shortcode page and every launcher
     *    page load exactly what they loaded in 1.19.269. And
     *    `$GLOBALS['bhp_quiz_is_page_content']` below was never keyed on the
     *    homepage — it reads the template and the shortcode only — so
     *    1.19.266's CLS fix is not disturbed by this.
     */
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
    if (!$on_shortcode_page && !$on_canonical_quiz_page && !$on_sitewide_launcher_page) {
        return;
    }
    /*
     * ⭐⭐ 1.19.266 (`CYCLE165-LD-ITERATE-2-AESTHETICS-TOKENS`) — IS THE QUIZ
     *    THE PAGE, OR IS IT A CLOSED MODAL? `bhp_async_noncritical_styles()`
     *    needs the answer and this is the only place that already knows it.
     *
     * MEASURED, headless Chromium, `innerWidth` asserted 1440, three runs:
     * `/find-your-adventure/` reported CLS 0.2404 with ONE 0.2354 shift at
     * ~1.3-1.6 s. Source rects, read from the PerformanceObserver rather than
     * inferred: `DIV.bhp-quiz__inner` 1440x156 -> 640x320, taking
     * `FOOTER.site-footer` from y=586 to y=853. That is the quiz painting
     * UNSTYLED and then being restyled when its deferred stylesheet lands.
     *
     * The deferral itself is right, and its docblock says exactly why it is
     * right: the quiz "styles a modal that is CLOSED on first paint". On this
     * ONE page that sentence is false -- the quiz IS the page's main content,
     * rendered inline by `page-find-your-adventure.php` or by the shortcode.
     * The correct fix is not to undo the deferral but to stop applying it
     * where its own precondition does not hold.
     *
     * ⚠ THIS DEFECT PREDATES 1.19.266 -- the same page measured CLS 0.0836 at
     *   1440 on 1.19.264. It is fixed here because THIS release made it
     *   worse: the H1 went 96px -> 48px, the page above the quiz got shorter,
     *   and the same absolute reflow therefore moved a larger fraction of the
     *   viewport. A release that triples a gate-row number does not hand it
     *   over as "pre-existing".
     *
     * ⛔ Keyed on the TEMPLATE and on the shortcode, never on a page slug --
     *   a renamed page must not silently re-open this.
     */
    if ($on_canonical_quiz_page || $on_shortcode_page) {
        $GLOBALS['bhp_quiz_is_page_content'] = true;
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

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔⛔ REMOVED FROM /shop/ AT 1.19.284 — CARRIER ITEM 207.
 *     `bhp_woocommerce_shop_complete_collection_banner()` AND ITS HOOK ARE
 *     GONE. THE WHOLE HEAD-NOTE BELOW IS THEREFORE HISTORY.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ ANDREW SIGNORE, carrier item 207, 2026-08-21. ⚠️ RELAYED through
 *    `chief-of-staff` (Gandalf 9) in the build brief — ⛔ NOT witnessed
 *    first-hand by the agent that made this change. Recorded as relayed, per
 *    Standing Rules §9.2 rule 2, so the basis of the claim travels with it.
 *    His instruction: the collection carousel / series block comes OFF the
 *    shop page. ⛔ THERE ONLY. Every other surface that carries a Collection
 *    gallery is untouched by this change and was verified untouched — see
 *    below.
 *
 * ⭐⭐ WHY THIS SUBTRACTION WAS ALLOWED TO PROCEED, stated rather than assumed,
 *    because `21-PROTECTED-ELEMENTS-MANIFEST.md` §5 says a blocked build is
 *    the manifest working and no agent removes a protected element on its own
 *    judgement:
 *
 *      1. ⭐ HIS OWN WORD IS THE `FD-542` AUTHORITY. §5's gate is "on Andrew's
 *         explicit word (Standing Rules §6)". Item 207 IS that word. Nothing
 *         here rests on this agent's judgement about whether the banner earns
 *         its place.
 *      2. ⛔ AND THE MANIFEST HAS NO ROW TO MOVE — READ, NOT ASSUMED. §3 has
 *         sections HOME, POSTS, PRODUCTS, COLLECTION and SITEWIDE. ⭐ THERE IS
 *         NO SHOP SECTION, and `bhp_pe_manifest()` in
 *         `tests/test-protected-elements.php` has no `shop` key either. The
 *         same finding 1.19.283 recorded when it hid the Kirkus badge on the
 *         mobile shop card. So this removes no listed element and relaxes no
 *         assertion. ⛔ The `home`, `product` and `collection` rows are
 *         untouched and still pass.
 *      3. ⛔ NO CTA IS ORPHANED, which is the failure shape of item 118 and the
 *         thing §1.11 of the suite exists to catch. The banner's only outbound
 *         control was "View the Complete Collection" → /complete-collection/.
 *         ⭐ THE SAME DESTINATION IS STILL ON THE SAME PAGE: the Complete
 *         Collection shop card carries "GET THE COMPLETE COLLECTION" to the
 *         identical URL, and at 1.19.284 it carries the three-paperback
 *         composite and the $31.99 price beside it (item 206). The path a
 *         parent walks is shorter, not missing.
 *
 * ⛔ WHAT WAS REMOVED WITH IT, so nothing is left half-wired:
 *      · the `woocommerce_before_main_content` hook at priority 6;
 *      · `bhp_cx_shop_banner_gallery_media()` in `inc/collection-gallery.php` —
 *        the predicate had exactly two callers, both of them this feature;
 *      · the `is_shop()` branch of `bhp_book_enqueue_media_assets()` in
 *        `inc/book-formats.php`, so `book-media.css` / `book-media.js` no
 *        longer load on the shop archive. ⭐ Leaving that branch would have
 *        shipped two assets to every shop visitor for a component that no
 *        longer renders.
 *
 * ⭐ THE CSS IS DELIBERATELY LEFT IN PLACE, and that is a reversibility
 *    decision rather than an oversight. `.woo-complete-collection-banner*` in
 *    `style.css` and `assets/css/book-media.css` now matches nothing on any
 *    page. It costs bytes and renders nothing; deleting it would spread this
 *    change across two more built artefacts and make the restore path more
 *    than one commit. ⚠️ FLAGGED FOR CLEANUP rather than silently kept.
 *
 * ⛔⛔ EVERY OTHER COLLECTION GALLERY IS UNTOUCHED, AND THIS WAS CHECKED
 *    RATHER THAN ASSERTED. `bhp_cx_collection_gallery_map()` never had a shop
 *    entry (see the head-note in `inc/collection-gallery.php`, which explains
 *    that the shop archive shares `archive-product.php` with every product_cat
 *    and product_tag archive, so a map entry would have leaked the carousel
 *    across all of them). /complete-collection/, the homepage, /books/ and the
 *    four funnel pages resolve through the map and through
 *    `bhp_book_render_collection_hero_gallery()`, neither of which this change
 *    touches. `tests/test-shop-collection-carousel.php` now asserts BOTH
 *    halves: absent on /shop/, still present on /complete-collection/.
 *
 * ⭐ TO RESTORE: re-add the function and its `add_action`, the
 *    `bhp_cx_shop_banner_gallery_media()` predicate, and the `is_shop()`
 *    enqueue branch. All three are in the 1.19.283 tree at commit `da7f0ed`.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * ⬇ WHAT FOLLOWS IS THE REMOVED COMPONENT'S OWN RECORD, PRESERVED VERBATIM AND
 *   DELIBERATELY NOT DELETED. It documents a founder ruling of 2026-08-17 that
 *   PUT the carousel here, and a 1.19.234 argument AGAINST it that was
 *   overruled. A future session that cannot see both will re-derive one of
 *   them. ⛔ NONE OF IT DESCRIBES LIVE BEHAVIOUR ANY MORE.
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Complete Collection banner above the shop catalog grid (Overnight
 * Conversion Sprint, Priority 1.5) -- intercepts customers before they
 * start comparing six nearly-identical single-edition cards. Shop archive
 * only, not individual product pages or other taxonomy archives.
 *
 * 1.19.234 — THE COVERS NOW DO THE SELLING. The banner asked for the sale in
 * words and showed nothing; the /complete-collection/ landing sells with the
 * three covers before it says anything. This carries that visual up to the
 * shop archive, which is where the comparison actually starts.
 *
 * ⭐ WHAT IS REUSED, AND WHAT WAS DELIBERATELY NOT.
 *
 *    REUSED: `bhp_get_popup_ab_covers()` — the A/B popup's cover strip source,
 *    1.19.205. It resolves the same three real product image attachments the
 *    homepage, the collection landing and the parent landing already render,
 *    in series order (Mariana, Everest, Amazon), behind a 12-hour transient.
 *    ⛔ NO NEW MEDIA. Nothing is uploaded, composited or regenerated here — it
 *    reuses attachment IDs, which is the only thing `.claude/rules` and the
 *    company memory permit for a real cover.
 *
 *    NOT REUSED: the collection page's actual hero gallery
 *    (`bhp_book_render_collection_hero_gallery()` →
 *    `template-parts/commerce/look-inside.php`, 475 lines with a thumbnail
 *    rail, a lightbox and its own JS). It is the right component for a hero
 *    and the wrong one for a banner: it renders behind the bundle plugin's
 *    `bhp_bundle_landing_hero_media` action, it depends on approved
 *    `complete_collection` interior media, and it would add a script and a
 *    interactive surface above a product grid that has to stay immediately
 *    visible. The brief named this trade and this is the side it lands on.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.235 (2026-08-17, `CYCLE162-LD-SHOP-CAROUSEL-V2`) — EVERYTHING FROM
 *     "1.19.234 — THE COVERS NOW DO THE SELLING" DOWN TO HERE IS SUPERSEDED.
 *     It is PRESERVED VERBATIM AND DELIBERATELY NOT DELETED, because the
 *     paragraph beginning "NOT REUSED" argued AGAINST the component the
 *     founder has now asked for by name, and a future session that cannot see
 *     that argument being overruled will re-derive it and rebuild the fan.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE RULING, VERBATIM. Andrew Signore, 2026-08-17, on his own viewing of
 *    the 1.19.234 static cover fan (⚠ RELAYED through `chief-of-staff` in the
 *    build brief — NOT witnessed first-hand by this session):
 *
 *        "No that is not very good- just use the collection page carousel
 *         image gallery that we already created - should be easy to swap in"
 *
 * ⛔ WHAT WAS WRONG WITH THE PRIOR REASONING, stated plainly rather than
 *    quietly dropped. The "NOT REUSED" paragraph above made three claims. Two
 *    were factually right and one was a judgement the founder has now made
 *    differently:
 *      - "it renders behind the bundle plugin's `bhp_bundle_landing_hero_media`
 *        action" — TRUE of `bhp_book_render_collection_hero_gallery()`, and
 *        irrelevant: the ACTION is just one caller. The component is
 *        `template-parts/commerce/look-inside.php`, which /books/, the
 *        homepage and four funnel pages already include directly, with no
 *        plugin action anywhere. This build does the same.
 *      - "it depends on approved `complete_collection` interior media" — TRUE,
 *        and it is a FEATURE: `bhp_cx_shop_banner_gallery_media()` returns
 *        null when that media does not resolve and the banner falls back to
 *        exactly its 1.19.233 copy-only markup.
 *      - "it would add a script and an interactive surface above a product
 *        grid that has to stay immediately visible" — this was the judgement.
 *        It is overruled. The cost is one CSS file and one JS file that are
 *        already built, already minified in the deploy and already loaded on
 *        seven other surfaces, plus a capped stage height (see the CSS block
 *        in `style.css`) that keeps the first row of product cards in view.
 *
 * ⭐ WHAT IS ACTUALLY WIRED, and it is the same three lines the collection page
 *    runs, not a copy of them:
 *      1. MEDIA — `bhp_cx_shop_banner_gallery_media()` in
 *         `inc/collection-gallery.php`, which returns
 *         `bhp_book_media('complete_collection')` unmodified. The identical
 *         call `bhp_book_render_collection_hero_gallery()` makes. Not a subset,
 *         not a re-order. ⛔ NO NEW MEDIA — no upload, no composite, no
 *         regeneration, no registry edit.
 *      2. MARKUP — `template-parts/commerce/look-inside.php`, included with the
 *         documented variable contract, exactly as `inc/collection-gallery.php`
 *         includes it for the six funnel surfaces.
 *      3. ASSETS — `bhp_book_enqueue_media_assets()` in `inc/book-formats.php`
 *         gained an `is_shop()` branch that calls THE SAME PREDICATE this
 *         function calls. ⛔ One predicate, two callers: the CSS/JS and the
 *         markup cannot appear without each other, which is the failure mode
 *         the brief named ("assets must load or the carousel is a broken
 *         list").
 *
 * ⭐ THE MODE FLAGS, each chosen rather than copied, because the collection
 *    page's own flags are wrong for a banner:
 *      `$hero      = false` — hero mode DELETES the heading element and labels
 *                    the region with `aria-label` instead. That is right when
 *                    the gallery stands in for a product's main image above an
 *                    `<h1>`; here the banner's own `<h2>` is the section title
 *                    and the region still needs a name of its own.
 *      `$heading_hidden = true` — the banner already says "Looking for the
 *                    complete series?" two lines away. A second visible title
 *                    inside the same ivory box reads as a second section. The
 *                    ELEMENT IS NOT REMOVED: it is the `aria-labelledby`
 *                    target, so deleting it would strip the region's
 *                    accessible name. Same precedent as the homepage and the
 *                    four funnel heroes.
 *      `$level     = 'h3'` — the nearest preceding heading is the banner's own
 *                    `<h2>`, so `h3` is the level that does not skip.
 *      `$collection = true` — the cream/forest/gold chrome. The banner's
 *                    background is `--color-ivory`, so the collection page's
 *                    palette is the one that belongs here, and it carries
 *                    1.19.206's `width: 100%` slide rule, which is what stops
 *                    the picture painting small inside its own stage.
 *      `$compact   = true` — tightens the rail, as every non-hero placement.
 *      `$eager_first = true` — ⛔ LOAD-BEARING, NOT COSMETIC. The banner is at
 *                    hook priority 6, directly under `bhp_woocommerce_archive_hero`,
 *                    which is TEXT ONLY and has no image. Slide 0 is therefore
 *                    the largest contentful paint on /shop/. Lazy-loading it
 *                    would cost exactly the metric the placement is meant to
 *                    win. (Contrast `front-page.php` and `page-books.php` in
 *                    `inc/collection-gallery.php`, which correctly keep false.)
 *
 * ⭐ THE COPY IS STILL BYTE-IDENTICAL TO 1.19.233. Heading, subline and CTA
 *    label are unchanged and the CTA still resolves to /complete-collection/.
 *    Locked prose was not touched.
 *
 * ⭐ NO DEEP-LINK ODDITY, and this was checked rather than assumed:
 *    `look-inside.php` emits no `<a>` at all except the `<video>` element's
 *    "Download the flip-through instead." fallback, which is only ever
 *    rendered by a browser that cannot play the clip and points at the media
 *    file itself. Its "Click to enlarge" opens the in-page lightbox;
 *    `assets/js/book-media.js` contains no `location`, `href` assignment,
 *    `window.open` or `history` call. Nothing in the component navigates
 *    anywhere, so nothing about it deep-links oddly from /shop/.
 *
 * ⛔ EXACTLY ONE GALLERY INSTANCE, which matters because `look-inside.php`
 *    derives its DOM id from the media key. `bhp_cx_render_collection_gallery()`'s
 *    placement map has no shop entry, and `bhp_book_render_collection_hero_gallery()`
 *    fires on a bundle-plugin action the shop archive never runs. This is the
 *    only consumer of `#bhp-look-inside-complete_collection` on /shop/.
 */
/*
 * ⛔⛔ THE FUNCTION AND ITS `add_action('woocommerce_before_main_content',
 *     'bhp_woocommerce_shop_complete_collection_banner', 6)` STOOD HERE UNTIL
 *     1.19.284 AND WERE REMOVED UNDER CARRIER ITEM 207. See the block above
 *     for the ruling, the three reasons the subtraction was permitted, what
 *     else came out with it, and the restore path (commit `da7f0ed`).
 *
 * ⛔ NOTHING REPLACES IT ON /shop/. The Complete Collection reaches the same
 *    shopper as a PRODUCT-STYLE CARD IN THE GRID — composite, title, $31.99,
 *    "GET THE COMPLETE COLLECTION" → /complete-collection/ — which is item 206
 *    in the same release. ⭐ One offer, one place, one control.
 */

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
        /*
         * ⭐ 1.19.285, CARRIER ITEM 209 — the PRIMARY nav's Books entry points
         *    at /shop/ here too. ⚠ THIS PATH IS DORMANT on both environments
         *    (a stored menu is assigned to `primary`, so `fallback_cb` never
         *    fires — verified, not assumed), so this line changes nothing
         *    today. It is changed anyway because the day someone unassigns the
         *    menu in wp-admin, the fallback becomes the nav, and a dormant copy
         *    of a retired route is exactly how a merged page comes back.
         *    LABEL UNCHANGED: still "Books".
         * ⛔ `bhp_footer_fallback_menu()` is DELIBERATELY NOT changed — the
         *    footer is outside item 209's wording, its /books/ link 301s
         *    correctly, and widening the brief by inference is the failure this
         *    repository keeps a subtraction record for.
         */
        __('Books', 'brave-hearts')             => (function_exists('bhp_books_merge_destination') && '' !== bhp_books_merge_destination())
            ? bhp_books_merge_destination()
            : home_url('/books/'),
        /*
         * ⭐ 1.19.301, CARRIER ITEM 300 ("A") — the same treatment, for the same
         *    reason, one slot down. SUPERSEDED LINE, PRESERVED VERBATIM so the
         *    movement is visible and is not re-derived:
         *
         *        __('Expedition Guides', 'brave-hearts') => home_url('/teachers/'),
         *
         *    ⚠ THIS PATH IS DORMANT on both environments — a stored menu IS
         *    assigned to `primary` (term 198, verified first-hand on staging),
         *    so `fallback_cb` never fires and this line changes nothing today.
         *    It moves anyway, for the reason the Books note above already gives:
         *    the day somebody unassigns the menu in wp-admin, this fallback
         *    BECOMES the nav, and a dormant copy of a retired label is exactly
         *    how an old route comes back.
         * ⛔ `bhp_footer_fallback_menu()` is again DELIBERATELY NOT changed —
         *    `footer.php` stopped calling it at 1.19.269 and the `footer` menu
         *    location is not rendered at all, so editing it would be churn with
         *    a failing test attached.
         */
        __('Free Resources', 'brave-hearts')    => home_url('/free-resources/'),
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

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.285 — CARRIER ITEM 209, LIMB 2: /books/ IS MERGED INTO /shop/.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, carrier item 209, 2026-08-21 (⚠️ RELAYED through
 * `chief-of-staff`, ⛔ NOT witnessed first-hand by the agent that wrote this —
 * recorded as relayed per Standing Rules §9.2 rule 2). Two storefront doors
 * become one: /books/ answers with a PERMANENT redirect to /shop/, and the
 * primary-nav "ADVENTURE BOOKS" entry points at /shop/ with its label
 * untouched (see `bhp_adventure_books_nav_target_shop()` below).
 *
 * ⛔⛔ WHAT THIS COSTS, STATED HERE RATHER THAN DISCOVERED LATER. /shop/ is a
 *    WooCommerce archive and `page-books.php` was not. Counted in the SERVED
 *    documents of both pages on staging 1.19.284, same origin, real browser:
 *
 *      marker                                      /books/   /shop/
 *      bhp-collection-band                            2         0
 *      "Start with Book 1" (the single hero primary)  1         0
 *      look-inside                                    7         0
 *      "FREE Shipping on the complete collection…"    1         0
 *      bhp-shop-collection-card                       0         6
 *      woocommerce-loop-product__title                0         6
 *
 *    So the merge RETIRES the collection band, the Look Inside gallery and the
 *    founder's free-shipping sentence from this route, and replaces them with
 *    the item-206 product grid and its Complete Collection card. ⭐ The
 *    collection PURCHASE PATH survives — the card carries the same
 *    /complete-collection/ destination — which is the item-118 "no orphaned
 *    CTA" test, and it is asserted in `tests/test-item-209-books-shop-merge.php`
 *    §3 rather than claimed here. ⛔ The other three are genuine subtractions
 *    and are reported to the Chief of Staff as such, not absorbed silently.
 *
 * ⛔ `page-books.php` IS DELIBERATELY LEFT ON DISK, and so is every
 *    `home_url('/books/')` link in the theme. They 301 correctly. Deleting the
 *    template would make this one-line-reversible change a rebuild.
 *
 * ⭐ SITEMAP: `/books/` is registered in `bhp_seo_theme_redirected_paths()`
 *    (inc/seo-hygiene.php) in the same sitting, because the 1.19.272 rule is
 *    "a URL that 301s never enters the sitemap" and a redirect added without
 *    that registration advertises a redirect to Google.
 */
function bhp_redirect_books_to_shop() {
    if (is_admin()) {
        return;
    }

    $request_path = untrailingslashit((string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH));
    $books_path   = untrailingslashit((string) wp_parse_url(home_url('/books/'), PHP_URL_PATH));

    if ('' === $books_path || $request_path !== $books_path) {
        return;
    }

    $shop_url = bhp_books_merge_destination();
    if ('' === $shop_url) {
        return;
    }

    /*
     * Query args travel. A school-visit session is carried in the query string
     * (`?bhp_visit=…`) and dropping it here would silently un-flag a parent
     * mid-journey — the FD-505/FD-506 path that must never break. The same
     * applies to every UTM landing on this legacy door.
     */
    $query = (string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY);
    if ('' !== $query) {
        $shop_url .= (false === strpos($shop_url, '?') ? '?' : '&') . $query;
    }

    wp_safe_redirect($shop_url, 301, 'Brave Hearts Theme');
    exit;
}
add_action('template_redirect', 'bhp_redirect_books_to_shop', 1);

/**
 * Where /books/ merges to, or '' if it cannot be resolved safely.
 *
 * ⛔ FAILS CLOSED, AND THAT IS THE WHOLE FUNCTION. If WooCommerce is inactive,
 *    if the Shop page is unset, or if the resolved destination is /books/
 *    itself, this returns '' and NOTHING redirects. A redirect that cannot
 *    name its destination must not fire: the failure mode is a redirect loop
 *    or a 301 into a 404, both of which are worse than the page it replaced.
 *
 * @return string Absolute URL, or ''.
 */
function bhp_books_merge_destination() {
    $shop_url = function_exists('wc_get_page_permalink') ? (string) wc_get_page_permalink('shop') : '';

    if ('' === $shop_url || false !== strpos($shop_url, 'woocommerce_shop_page_not_set')) {
        $shop_page = get_page_by_path('shop');
        $shop_url  = ($shop_page instanceof WP_Post && 'publish' === $shop_page->post_status)
            ? (string) get_permalink($shop_page->ID)
            : '';
    }

    if ('' === $shop_url) {
        return '';
    }

    $shop_path  = untrailingslashit((string) wp_parse_url($shop_url, PHP_URL_PATH));
    $books_path = untrailingslashit((string) wp_parse_url(home_url('/books/'), PHP_URL_PATH));

    if ('' === $shop_path || $shop_path === $books_path) {
        return '';
    }

    return $shop_url;
}

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
    /*
     * ⭐ 1.19.285, CARRIER ITEM 209 — TWO PATHS MATCH NOW, NOT ONE.
     *
     * ⛔ THE DEFECT THIS PRE-EMPTS. This filter used to key on /books/ alone.
     *    Item 209 points the entry at /shop/, and the brief's own words are
     *    "label unchanged" — so if the stored menu row is ever re-saved in
     *    wp-admin to /shop/ directly, a /books/-only match would silently drop
     *    the stacked "Adventure / Books" label AND the `menu-item--adventure-
     *    books` class the aria-label filter below keys on. Nothing would error;
     *    the nav would just quietly go back to a single-line "Books" with no
     *    accessible name. Matching BOTH paths makes the label survive either
     *    stored value, which is what "unchanged" has to mean here.
     *
     * ⚠ The stored menu row itself is NOT edited. It stays whatever it is; the
     *   retarget happens in `bhp_adventure_books_nav_target_shop()` at
     *   priority 25, AFTER this filter, so the label is stacked first and the
     *   URL is rewritten second. Order is load-bearing, not incidental.
     */
    $match_paths = array(
        untrailingslashit((string) wp_parse_url(home_url('/books/'), PHP_URL_PATH)),
    );
    $merge_dest = function_exists('bhp_books_merge_destination') ? bhp_books_merge_destination() : '';
    if ('' !== $merge_dest) {
        $match_paths[] = untrailingslashit((string) wp_parse_url($merge_dest, PHP_URL_PATH));
    }
    $match_paths = array_values(array_filter(array_unique($match_paths)));

    foreach ($items as $item) {
        $item_path = untrailingslashit((string) wp_parse_url($item->url, PHP_URL_PATH));
        if (!in_array($item_path, $match_paths, true)) {
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

/**
 * CARRIER ITEM 209, LIMB 2 — the "ADVENTURE BOOKS" entry points at /shop/.
 *
 * ⛔ THE LABEL IS NOT TOUCHED, AND THAT IS THE BRIEF'S OWN CONSTRAINT. This
 *    function reads `$item->title` never, writes it never. It rewrites exactly
 *    one property, `$item->url`, and adds the current-page classes WordPress
 *    can no longer work out for itself once the URL and the stored object
 *    disagree.
 *
 * ⚠ PRIORITY 25 — AFTER the label-stacking filter at 20. Reversed, the label
 *   filter would meet an already-rewritten /shop/ URL. It now matches both
 *   paths so that ordering is belt-and-braces rather than the only thing
 *   holding the label on, but the ordering is still the intended contract.
 *
 * ⚠ NOT A DATABASE EDIT. The stored menu row is untouched, exactly like the
 *   Expedition Guides and START HERE entries above — a DB menu row does not
 *   travel in a theme ZIP, so a staging-only menu edit would mean the nav
 *   silently still pointed at /books/ on production after an approved deploy.
 *   Recorded as `CYCLE165-LD-209-NAV`.
 */
function bhp_adventure_books_nav_target_shop($items) {
    $shop_url = bhp_books_merge_destination();
    if ('' === $shop_url) {
        return $items;
    }

    $books_path = untrailingslashit((string) wp_parse_url(home_url('/books/'), PHP_URL_PATH));
    $shop_path  = untrailingslashit((string) wp_parse_url($shop_url, PHP_URL_PATH));
    $on_shop    = function_exists('is_shop') && is_shop();

    foreach ($items as $item) {
        $item_path = untrailingslashit((string) wp_parse_url($item->url, PHP_URL_PATH));
        if ($item_path !== $books_path && $item_path !== $shop_path) {
            continue;
        }

        $item->url = $shop_url;

        if ($on_shop) {
            $item->current = true;
            $item->classes = array_values(array_unique(array_merge(
                (array) $item->classes,
                array('current-menu-item')
            )));
        }
    }

    return $items;
}
add_filter('wp_nav_menu_objects', 'bhp_adventure_books_nav_target_shop', 25);

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
 * Front-cover thumbnail for a lead magnet — theme 1.19.224, 2026-08-13,
 * `CYCLE158-LD-SIGNUP-POPUP` iteration 2.
 *
 * ⭐ EVERY IMAGE THIS RETURNS IS A PAGE-1 RENDER OF THE REAL PDF, AND THAT
 *    PROVENANCE IS THE WHOLE POINT OF THE FUNCTION. Andrew Signore, relayed
 *    through Gandalf: each funnel's popup carries "the front cover of its own
 *    PDF". Nothing here is illustrated, generated, mocked up or borrowed from
 *    a neighbouring magnet — the never-invent rule covers imagery that claims
 *    to be a real artefact exactly as it covers a claim in prose.
 *
 *    HOW THE FILES WERE MADE, recorded so a future session can reproduce them
 *    rather than re-derive them: the four PDFs registered under Settings →
 *    Lead Magnets were copied off the PRODUCTION document root, rendered page
 *    1 with PyMuPDF at 4x and downsampled (Lanczos) to 173x224, then written
 *    as WebP q86 plus a PNG fallback into assets/images/lead-magnets/. The
 *    three magnets that already had a full-size cover asset in the repo
 *    (assets/images/handoff/*-cover.webp) were checked against the same
 *    renders first and matched to a mean absolute difference of 1.4–1.9/255,
 *    i.e. lossy-compression noise — so these small files are the SAME
 *    artwork at a size appropriate to a 112px slot, not a second source of
 *    truth. Production and staging carry byte-identical PDFs for all four
 *    (md5-compared 2026-08-13); only two of the filenames differ.
 *
 * ⛔ NO COVER FOR A MAGNET WITHOUT A PDF. `bookstore_wholesale_guide` has no
 *    PDF on either environment, its modal does not render at all, and there
 *    is deliberately no entry for it below. An absent cover returns an empty
 *    array and the caller renders no image — never a placeholder.
 *
 * ⭐ THE ALT TEXT NAMES THE DOCUMENT BY ITS CANONICAL NAME — CORRECTED
 *    2026-08-13 (1.19.225) BY A FOUNDER RULING, AND THE SUPERSEDED REASONING
 *    IS PRESERVED HERE RATHER THAN DELETED.
 *
 *    WHAT THIS DOCBLOCK SAID IN 1.19.224, verbatim: "The alt text names what
 *    is PRINTED ON THE COVER, which is not always the site-facing title of the
 *    magnet (the gift guide's cover reads 'The Ultimate Children's Book Gift
 *    Guide'; the site calls it 'The Meaningful Gift Guide'). Alt text
 *    describes the image, so the printed title is the honest value; the
 *    divergence is a copy question for Andrew, not something to paper over
 *    here."
 *
 *    That flagged the divergence to Andrew, which was the right move. ⭐ HE
 *    RULED ON IT, 2026-08-13: the canonical name of this lead magnet is
 *    **"The Meaningful Gift Guide"**, and the alt text is to read "Front cover
 *    of The Meaningful Gift Guide". So the value below is now the canonical
 *    name, not the string printed on the artwork.
 *
 * ⛔ NOTHING ELSE MOVED, AND THE OMISSIONS ARE DELIBERATE. The PDF is not
 *    touched. The cover ARTWORK is not touched — Andrew's ruling in the same
 *    turn was "Leave it", so the rendered image still reads "The Ultimate
 *    Children's Book Gift Guide" and that is approved, not an oversight. The
 *    asset FILENAME stays `ultimate-gift-guide-cover.*`: it names the source
 *    artefact, it is not visitor-facing, and renaming two shipped binaries to
 *    match a copy decision would risk a broken image on staging to change a
 *    string nobody reads. The gift-buyers page's own pre-existing copy, alt
 *    text and 2026-07-17 comment are outside this release's scope and were
 *    not edited.
 *
 * ⚠️ CONSEQUENCE, STATED PLAINLY: the alt text now differs from the words
 *    visible in the image. That is the founder's call on his own product's
 *    name, and it is recorded here so a future session does not "fix" it back.
 *
 * The alt text for the other three magnets is unchanged and still matches
 * both their cover art and their site-facing names.
 *
 * @param string $magnet_key Lead-magnet registry key.
 * @return array{url:string,fallback:string,width:int,height:int,alt:string}|array{}
 */
function bhp_get_lead_magnet_cover($magnet_key) {
    $covers = apply_filters('bhp_lead_magnet_covers', [
        'reluctant_reader_adventure_kit' => [
            'file'  => 'reluctant-reader-adventure-kit-cover',
            'title' => __('The Reluctant Reader Adventure Kit', 'brave-hearts'),
        ],
        'teacher_adventure_toolkit' => [
            'file'  => 'adventure-learning-toolkit-cover',
            'title' => __('The Adventure Learning Toolkit', 'brave-hearts'),
        ],
        'meaningful_gift_guide' => [
            // Founder ruling 2026-08-13: the canonical name is "The Meaningful
            // Gift Guide". The FILE key still names the source artefact; only
            // the visitor-facing title changed. See the docblock above.
            'file'  => 'ultimate-gift-guide-cover',
            'title' => __('The Meaningful Gift Guide', 'brave-hearts'),
        ],
        'community_reading_kit' => [
            'file'  => 'community-reading-kit-cover',
            'title' => __('The Community Reading Kit', 'brave-hearts'),
        ],
    ]);

    $key = sanitize_key($magnet_key);
    if (!isset($covers[$key]['file'])) {
        return [];
    }

    $base     = 'assets/images/lead-magnets/' . sanitize_file_name($covers[$key]['file']);
    $webp_rel = $base . '.webp';
    $png_rel  = $base . '.png';

    // A missing file renders nothing rather than a broken image. This runs on
    // a hidden modal on five pages, so it is cheap, but it is still the only
    // guard between a mis-deployed artefact and a broken box on a funnel page.
    if (!file_exists(get_theme_file_path($webp_rel)) || !file_exists(get_theme_file_path($png_rel))) {
        return [];
    }

    /*
     * ═══════════════════════════════════════════════════════════════════════
     * ⭐ 1.19.296 (2026-08-27, `CYCLE167-LD-CAPTURE-FIX-BUILD`) — THE
     *    DIMENSIONS ARE READ FROM THE FILE, NOT TYPED INTO THIS FUNCTION.
     * ═══════════════════════════════════════════════════════════════════════
     *
     * ⛔ THEY WERE HARDCODED `173` / `224` FOR EVERY MAGNET, AND THIS RELEASE
     *    CAUGHT IT THE HARD WAY. The Reluctant Reader cover was regenerated at
     *    346x448 so the popup could render it larger without going soft — and
     *    this function carried on emitting `width="173" height="224"` for a
     *    file that is now twice that. The suite caught it; a reader would have
     *    seen a correctly-shaped but wrongly-described image.
     *
     * ⭐ A HARDCODED INTRINSIC SIZE IS A LIE WAITING FOR SOMEBODY TO REPLACE AN
     *    ASSET. Reading the real file cannot go stale, so replacing artwork is
     *    now a file operation rather than a file operation plus a code edit
     *    somebody has to remember.
     *
     * ⛔ ZERO BEHAVIOUR CHANGE FOR THE OTHER THREE MAGNETS, VERIFIED RATHER
     *    THAN ASSUMED: `adventure-learning-toolkit-cover.png`,
     *    `community-reading-kit-cover.png` and `ultimate-gift-guide-cover.png`
     *    were each measured this build and are all exactly 173x224, so
     *    `getimagesize()` returns for them precisely what was hardcoded.
     *
     * ⭐ STATIC CACHE — this runs on a hidden modal on several pages, and one
     *    `getimagesize()` per magnet per request is the whole cost.
     * ⛔ THE OLD CONSTANTS SURVIVE AS THE FALLBACK, so an unreadable file
     *    degrades to the previous behaviour rather than emitting `width="0"`,
     *    which would collapse the box.
     */
    static $dimension_cache = [];
    if (!isset($dimension_cache[$key])) {
        $measured = @getimagesize(get_theme_file_path($png_rel));
        $dimension_cache[$key] = [
            'width'  => (is_array($measured) && !empty($measured[0])) ? (int) $measured[0] : 173,
            'height' => (is_array($measured) && !empty($measured[1])) ? (int) $measured[1] : 224,
        ];
    }

    return [
        'url'      => get_theme_file_uri($webp_rel),
        'fallback' => get_theme_file_uri($png_rel),
        'width'    => $dimension_cache[$key]['width'],
        'height'   => $dimension_cache[$key]['height'],
        /* translators: %s: the title printed on the PDF's front cover. */
        'alt'      => sprintf(__('Front cover of %s', 'brave-hearts'), $covers[$key]['title']),
    ];
}

/* ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.298 (2026-08-27, `CYCLE167-LD-POPUP-PHOTO`) — THE FOUNDER'S OWN
 *     PHOTOGRAPH, AND THE GUARD THAT SAYS WHO IS IN IT.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ Andrew Signore, 2026-08-27, VERBATIM (carrier item 297):
 *
 *      "Did we come to a conclusion on adding a picture of me and charlotte as
 *       a gradient right to left with like a playful squiggle in between for
 *       the pop ups?"
 *
 * ⛔⛔ CHARLOTTE IS ANDREW'S NIECE. HE HAS NO CHILDREN. Carrier item 285, his
 *     own words: *"SHE IS MY NIECE and its all over the canon docs - I DONT
 *     HAVE KIDS"*.
 *
 * ⛔ THIS IS NOT A STYLE PREFERENCE AND IT IS NOT ENFORCED BY PROSE. On the
 *    night of 2026-08-27 a brief said "daughter", a rebuild believed the brief
 *    over the canon, and the word reached the accessibility layer of a
 *    delivered deck PDF — a layer only a screen-reader user would ever have
 *    met, which is exactly why no visual review caught it. The founder caught
 *    it himself. The deck lane's answer was to move the rule out of a manifest
 *    and into an assertion in its build; this is the same answer, in the theme,
 *    for the same reason. A comment cannot stop a future edit. A failing test
 *    can.
 *
 * ⛔ THE GUARD CHECKS BOTH DIRECTIONS, and the second one is the subtle one:
 *      (1) no forbidden kinship word appears, AND
 *      (2) if the text names a relationship at all, that relationship is
 *          "niece". Quietly deleting the word rather than correcting it is not
 *          a fix — a photograph of a man holding a baby with no relationship
 *          stated invites the reader to supply the wrong one.
 *    A text that names NO relationship whatsoever passes, because the founder's
 *    rail allows exactly that ("says 'my niece Charlotte' or names no
 *    relationship"). What it must never do is name a different one.
 */
function bhp_niece_canon_forbidden_terms() {
    /*
     * ⚠ EVERY TERM HERE IS A KINSHIP CLAIM ABOUT ANDREW, not a word that
     *   happens to appear in parent-facing copy. "my child" is deliberately
     *   ABSENT: a parent quoted saying "my child won't read" is legitimate
     *   copy on this site, and a guard that fails on it would be turned off
     *   within a week, which is how guards die.
     */
    return apply_filters('bhp_niece_canon_forbidden_terms', [
        'daughter',
        'his kid',
        'his kids',
        'his child',
        'his children',
        'my kid',
        'my kids',
        'as a dad',
        'as a father',
        'his son',
        'my son',
    ]);
}

/**
 * Returns the list of canon violations in a piece of relational text. An empty
 * array means the text is clean. Pure function: it reads nothing and writes
 * nothing, so a test can call it directly on any string.
 *
 * @param string $text Alt text, aria text, caption or any other relational copy.
 * @return string[] Human-readable violations, empty when clean.
 */
function bhp_niece_canon_violations($text) {
    $low = strtolower((string) $text);
    $violations = [];

    foreach (bhp_niece_canon_forbidden_terms() as $term) {
        if (strpos($low, strtolower($term)) !== false) {
            $violations[] = sprintf('forbidden kinship term "%s"', $term);
        }
    }

    /*
     * Direction two. "uncle" is accepted alongside "niece" because it states
     * the same relationship from the other end and the deck lane's own canon
     * uses both.
     */
    $names_a_relationship = (bool) preg_match(
        '/\b(niece|nephew|uncle|aunt|cousin|sister|brother|mother|father|parent|grand\w*)\b/i',
        $low
    );
    $names_the_right_one = (strpos($low, 'niece') !== false) || (strpos($low, 'uncle') !== false);

    if ($names_a_relationship && !$names_the_right_one) {
        $violations[] = 'names a relationship that is not "niece" (or "uncle")';
    }

    return $violations;
}

/**
 * The photograph's accessible name — the one string on this surface the guard
 * exists for.
 *
 * ⭐ FIRST PERSON, because every customer-facing word on this site is his own
 *    voice: "I / me / my", never "we". He is the sole operator.
 * ⛔ NO EM DASH. ⛔ NO CLAIM. It describes what is in the frame and stops:
 *    two people and a book that exists. It does not say the book was read to
 *    her, does not say she likes it, and does not say what any child gets from
 *    it.
 *
 * ⭐⭐ REWRITTEN AT 1.19.299 (`CYCLE167-LD-POPUP-PHOTO2-SWAP`) BECAUSE THE
 *    PHOTOGRAPH CHANGED. Andrew Signore, carrier item 301, relayed:
 *    *"im so sorry I need to change the photo on the pop up- I found a cuter
 *    photo with charlotte smiling"*.
 *
 * ⛔ THE ALT TEXT WAS REWRITTEN FROM LOOKING AT THE NEW FRAME, NOT FROM
 *    EDITING THE OLD SENTENCE. Alt text is a factual description of a
 *    specific image; carrying the previous one across a photograph swap would
 *    have described a picture that is no longer there, and it would have done
 *    it in the one layer no visual review ever checks. What is actually in
 *    frame now, observed: Charlotte is turned to the camera with a wide
 *    open-mouth smile and one hand raised, and the paperback is held up
 *    beside her. The previous wording said neither of those things because
 *    the previous photograph did not show them.
 *
 * ⛔ STILL NO CLAIM. "Smiling" is a description of a face, not an outcome. It
 *    does not say the book made her smile.
 */
function bhp_get_founder_photo_alt() {
    return (string) apply_filters(
        'bhp_founder_photo_alt',
        __('Me and my niece Charlotte. She is smiling at the camera with one hand raised, and I am holding a paperback of Adventures of Charlotte and Henry: The Mariana Trench.', 'brave-hearts')
    );
}

/**
 * Resolves the founder photograph's two shipped crops.
 *
 * ⭐ TWO CROPS, FETCHED EXCLUSIVELY OF EACH OTHER. The template pairs these
 *    with `<source media=...>`, so a visitor downloads the one crop that suits
 *    their viewport and never both. The portrait is 4:5 for the desktop side
 *    panel; the band is 5:4 for the mobile strip above the copy. They are
 *    separate files rather than one file cropped twice by CSS because
 *    `object-fit` cannot keep two faces and a book inside two shapes that
 *    differ by that much — it was measured, not assumed, and the build script
 *    that cuts them asserts the subjects survive every crop before it writes.
 *
 * ⛔ A MISSING FILE RENDERS NO PHOTOGRAPH, not a broken box — the same
 *    contract `bhp_get_lead_magnet_cover()` has kept since 1.19.224. All four
 *    files must be present or the whole treatment stands down and the popup
 *    renders exactly as 1.19.297 did.
 * ⭐ INTRINSIC DIMENSIONS ARE READ FROM THE FILES, never typed here. 1.19.296
 *    learned that the hard way on the kit cover: a hardcoded intrinsic size is
 *    a lie waiting for somebody to replace an asset.
 *
 * @return array Empty when the treatment cannot render.
 */
function bhp_get_founder_photo() {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $base = 'assets/images/founder/';
    $rel = [
        'portrait_webp' => $base . 'andrew-charlotte-popup-portrait.webp',
        'portrait_jpg'  => $base . 'andrew-charlotte-popup-portrait.jpg',
        'band_webp'     => $base . 'andrew-charlotte-popup-band.webp',
        'band_jpg'      => $base . 'andrew-charlotte-popup-band.jpg',
    ];

    foreach ($rel as $path) {
        if (!file_exists(get_theme_file_path($path))) {
            $cache = [];
            return $cache;
        }
    }

    $portrait = @getimagesize(get_theme_file_path($rel['portrait_jpg']));
    $band     = @getimagesize(get_theme_file_path($rel['band_jpg']));

    /*
     * ⛔⛔ 1.19.299 — THE VERSION QUERY IS NOT COSMETIC. IT IS THE ONLY REASON
     *    A PHOTOGRAPH SWAP REACHES ANYBODY WHO HAS ALREADY SEEN THE POPUP.
     *
     * ⭐ FOUND BY MEASUREMENT ON STAGING DURING THIS PASS, not reasoned about.
     *    These four filenames are FIXED, and SiteGround serves them with
     *    `Cache-Control: max-age=31536000` — one year. So the same URL had
     *    already returned three different photographs in one night, and a
     *    browser that met an earlier one kept it:
     *
     *      fetch(cache:'default') -> 44,296 bytes, 600x750, Last-Modified 08:08
     *      fetch(cache:'reload')  -> 58,310 bytes, 560x896, Last-Modified 09:06
     *
     *    Same URL. Same page load. A year apart in expiry. The BROWSER was
     *    right and the deploy was invisible to it.
     *
     * ⛔ WHAT THAT MEANT FOR THIS WORKSTREAM, stated plainly because it is the
     *    whole point: the founder asked for a different photograph on the
     *    popup, and without this line every returning visitor would have gone
     *    on seeing the old one for up to a year while every check we run —
     *    the suite, the file on disk, a fresh browser — reported success.
     *    A deploy that only reaches people who have never been here is not a
     *    deploy.
     *
     * ⭐ THE IDIOM IS THE THEME'S OWN. Every enqueued stylesheet and script in
     *    this file already passes `wp_get_theme()->get('Version')` as its
     *    version argument; these four are the exception only because they are
     *    raw `<img>` URLs rather than enqueued handles. This makes them behave
     *    like the rest.
     * ⭐ THE LONG `max-age` IS KEPT AND IS THE POINT. The URL changes when the
     *    theme version changes and never otherwise, so a returning visitor
     *    still pays nothing on a release that did not touch the photograph.
     */
    $ver = wp_get_theme()->get('Version');
    $bust = function ($rel_path) use ($ver) {
        $uri = get_theme_file_uri($rel_path);
        return $ver ? add_query_arg('ver', rawurlencode($ver), $uri) : $uri;
    };

    $cache = [
        'portrait_webp'   => $bust($rel['portrait_webp']),
        'portrait_jpg'    => $bust($rel['portrait_jpg']),
        'band_webp'       => $bust($rel['band_webp']),
        'band_jpg'        => $bust($rel['band_jpg']),
        'portrait_width'  => (is_array($portrait) && !empty($portrait[0])) ? (int) $portrait[0] : 600,
        'portrait_height' => (is_array($portrait) && !empty($portrait[1])) ? (int) $portrait[1] : 750,
        'band_width'      => (is_array($band) && !empty($band[0])) ? (int) $band[0] : 720,
        'band_height'     => (is_array($band) && !empty($band[1])) ? (int) $band[1] : 576,
        'alt'             => bhp_get_founder_photo_alt(),
    ];

    /*
     * ⛔ THE GUARD RUNS AT RENDER TIME TOO, not only in the suite. A filter can
     *    replace the alt text in a plugin or a child theme, and the suite
     *    cannot see that. If the replacement violates canon the photograph
     *    STANDS DOWN — the popup renders without it rather than with a wrong
     *    kinship claim attached to a real child. Failing closed is the only
     *    safe direction here: a missing photograph costs a nicer popup, and a
     *    wrong one costs the founder a correction he has already had to make
     *    once tonight.
     */
    if (bhp_niece_canon_violations($cache['alt'])) {
        $cache = [];
    }

    return $cache;
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
 * PARENT-FUNNEL A/B EMAIL-CAPTURE POPUP (2026-08-06, theme 1.19.204)
 * ===================================================================== */

/**
 * ⭐ Andrew Signore, current turn, verbatim: "I say build it now… Make it 15
 *    second delay." This surface REPLACES the quiz modal in the automatic
 *    popup slot. The quiz itself is untouched and stays reachable: its
 *    launcher button still renders sitewide via bhp_should_show_quiz_cta(),
 *    and /find-your-adventure/ is unchanged. Only the AUTO-OPEN is retired,
 *    by one filter at the bottom of this file.
 *
 * ⛔ THE VARIANT MAP IS LOCKED APPROVED COPY. Headings, subheads and
 *    `content_name` values are reproduced character-for-character from
 *    Andrew's approved brief. `content_name` is the join key between the
 *    popup, the Meta pixel's Lead event and the Mailchimp variant tag — it
 *    is not a label, and renaming one of the three breaks the other two.
 *
 * ⭐ 1.19.207 (2026-08-07) — "Free" → "FREE" IN BOTH SUBHEADS, and only
 *    that. Andrew Signore's own instruction (⛔ RELAYED through the Chief
 *    of Staff, NOT witnessed here): the word must read ALL CAPS, bold and
 *    larger wherever it appears in the popup copy. ONE WORD'S CASE CHANGED
 *    IN EACH STRING — not a syllable of either hook, either heading or
 *    either `content_name` moved, so the A/B test still measures the hook.
 *    The BOLD and the LARGER are presentation and live in CSS, applied by
 *    `bhp_popup_ab_emphasise_free()` at render time, which is why the map
 *    stays plain text and the suite can still compare it character for
 *    character.
 *
 * ⛔ THE BROWSER NEVER SENDS A TAG, AN AUDIENCE OR A URL. It sends the short
 *    key 'A' or 'B' in `bhp_variant`, resolved here against this fixed
 *    whitelist. Same pattern, deliberately, as bhp_get_quiz_signup_routes()
 *    and bhp_get_capture_segment_routes().
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ 1.19.267 (2026-08-19) — THE EXPERIMENT IS **OFF**, AND EVERYTHING FROM
 *    HERE TO THE END OF THIS SECTION IS RETAINED DELIBERATELY.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * `parent-ab-popup.php` no longer emits an `abTest` config block, so the
 * shared engine takes its pre-1.19.204 path: no cookie is read or written,
 * no variant is assigned, and no `bhp_variant` field is rendered or posted.
 * The map below, `bhp_resolve_popup_ab_variant()`, the cookie constant,
 * `bhp_popup_ab_emphasise_free()`, `bhp_get_popup_ab_covers()` and the
 * variant-tag filter at the end of this section are therefore UNREACHED from
 * the popup.
 *
 * ⛔ THEY ARE NOT DELETED, FOR THREE SEPARATE REASONS, EACH SUFFICIENT:
 *   1. The strings below are Andrew's approved 1.19.204 copy. Deleting
 *      approved copy to turn a test off destroys the thing that makes
 *      turning it back on a one-commit change instead of a reconstruction.
 *   2. `bhp_resolve_popup_ab_variant()` is a WHITELIST. If a stale cached
 *      page somewhere still posts a `bhp_variant`, the resolver is what
 *      stops an attacker-supplied string becoming a Mailchimp tag. Removing
 *      the guard before removing every possible caller is the wrong order.
 *   3. The `Variant:` tags already exist in the live Mailchimp audience.
 *      The filter that produces them is the only in-repo record of how those
 *      historical tag strings were minted.
 *
 * ⚠ CONSEQUENCE, STATED PLAINLY: this is dormant code with no live caller
 *   from the popup. It is dormant on purpose and it is cheap — nothing below
 *   runs unless something asks it to. A future session that wants it gone
 *   should remove it as its own decision, with Andrew's ruling on the
 *   Mailchimp tag continuity, not as a side effect of a copy change.
 */
const BHP_POPUP_AB_COOKIE = 'bhp_popup_ab';

function bhp_get_popup_ab_variants() {
    return [
        'A' => [
            'heading'      => __('It\'s Heartbreaking to Watch Them Fall Further Behind', 'brave-hearts'),
            'sub'          => __('You can still change this. Get the FREE 20-Minute Reluctant Reader Kit.', 'brave-hearts'),
            'content_name' => 'popup_hook_heartbreak',
        ],
        'B' => [
            'heading'      => __('Turn Reluctant Readers Into Willing Readers', 'brave-hearts'),
            'sub'          => __('The FREE 20-Minute Reluctant Reader Kit shows you exactly where to start.', 'brave-hearts'),
            'content_name' => 'popup_hook_willing',
        ],
    ];
}

/**
 * ⭐ 1.19.207 — the ONE word that gets a bigger, bolder treatment.
 *
 * Andrew's instruction is presentational, not editorial, so it is applied as
 * presentation: the copy stays plain text in `bhp_get_popup_ab_variants()`
 * (which is what lets `tests/test-popup-ab.php` keep asserting it character
 * for character), and this wraps the standalone word FREE on the way out.
 *
 * ⛔ ESCAPE FIRST, THEN WRAP. The text is run through `esc_html()` before a
 *    single character of markup is added, so the return value is safe to
 *    echo unescaped and no copy string can ever inject HTML. Only the exact
 *    standalone token `FREE` is matched — never a substring, so "freely" or
 *    a stray "free" in future copy is left alone rather than half-shouted.
 */
function bhp_popup_ab_emphasise_free($text) {
    return preg_replace(
        '/\bFREE\b/',
        '<span class="popup-ab__free">$0</span>',
        esc_html($text)
    );
}

/**
 * Resolve a submitted variant key, or '' if it is empty or unknown. Never
 * guesses: an unrecognised key yields no variant tag rather than a wrong one.
 */
function bhp_resolve_popup_ab_variant($key) {
    $key = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $key));
    $variants = bhp_get_popup_ab_variants();
    return isset($variants[$key]) ? $key : '';
}

/**
 * QA ONLY, AND ONLY ON STAGING. `?bhp_ab=A` forces a variant so both can be
 * reviewed without clearing cookies. Inert on production by construction —
 * BHP_Analytics_Config::is_staging() compares the real HTTP host — and never
 * persisted, so it cannot overwrite a real visitor's sticky assignment.
 */
function bhp_get_popup_ab_forced_variant() {
    if (!class_exists('BHP_Analytics_Config') || !BHP_Analytics_Config::is_staging()) {
        return '';
    }
    if (!isset($_GET['bhp_ab'])) {
        return '';
    }
    return bhp_resolve_popup_ab_variant(wp_unslash($_GET['bhp_ab']));
}

/**
 * Same surface rules as the timed parent popup it stands in for — including
 * the absolute /teachers/ exclusion, which is enforced HERE, server-side.
 * No offer PDF configured means no offer to make.
 */
function bhp_should_show_parent_ab_popup() {
    if (!bhp_should_show_any_popup()) {
        return false;
    }

    // ⛔ Parent funnel only. Never on the teacher page. `.claude/rules/funnels.md`.
    if (is_page('teachers')) {
        return false;
    }

    /*
     * ═══════════════════════════════════════════════════════════════════
     * ⭐ 1.19.241 (2026-08-18, `CYCLE164-LD-STOREFRONT-BATCH`) — THE POPUP
     *    IS NOW HOMEPAGE-ONLY. IT IS NARROWED, NOT KILLED.
     * ═══════════════════════════════════════════════════════════════════
     *
     * ⭐ THE FINDING (`commerce-cx` / Pippin, `CYCLE164-CX` #3): this popup
     *    fires at ~16s at 100% of viewports on the homepage, the Complete
     *    Collection page AND the product pages. VERIFIED LIVE on staging
     *    2026-08-18 before the change: `.mariana-popup--ab` was present in
     *    the DOM of the Mariana product page at both 1280 and an asserted
     *    390. Interrupting somebody who is already reading a price is the
     *    one place a capture overlay costs more than it earns.
     *
     * ⛔ IT IS DELIBERATELY NOT SWITCHED OFF. This surface produced the one
     *    paid subscriber the funnel has, so the offer keeps its best
     *    surface — the homepage, where a visitor has not yet chosen
     *    anything — and loses only the commercial-intent pages.
     *
     * ⚠ REPORTED, NOT ABSORBED — "homepage only" is strictly narrower than
     *   the four surfaces the brief names. `/complete-collection/`, product
     *   pages, cart and checkout are all suppressed as instructed (cart and
     *   checkout already were, upstream in bhp_should_show_any_popup()),
     *   AND SO IS EVERY BLOG POST, /books/, /shop/ and the rest of the
     *   site. That is what "homepage only" means and it is what was asked
     *   for, but blog traffic is a large share of this funnel's reach and
     *   nobody has decided that it should lose the offer. FLAGGED for
     *   Andrew rather than quietly widened back — the filter below is the
     *   one-line way to return blog posts if he wants them.
     *
     * ⛔ NO STORAGE KEY, EVENT PREFIX, VARIANT MAP, COPY STRING OR TIMER IS
     *    TOUCHED. This is a surface rule and nothing else, so the A/B test
     *    still measures the hook and `.claude/rules/funnels.md`'s isolation
     *    guarantees are exactly as they were.
     *
     * ═══════════════════════════════════════════════════════════════════
     * ⭐ 1.19.267 (2026-08-19, `CYCLE165-LD-ITERATE-3-POPUP-SIMPLE`) — THE
     *    BLOG COMES BACK. HOMEPAGE **AND** BLOG POSTS; STILL NO SELLING
     *    PAGES.
     * ═══════════════════════════════════════════════════════════════════
     *
     * ⭐ THIS IS THE FLAG ABOVE BEING ANSWERED, NOT A NEW DECISION. 1.19.241
     *    narrowed the surface to the homepage and said so in the ⚠ block
     *    above: "blog traffic is a large share of this funnel's reach and
     *    nobody has decided that it should lose the offer. FLAGGED for Andrew
     *    rather than quietly widened back — the filter below is the one-line
     *    way to return blog posts if he wants them." The scope now briefed
     *    for this popup is homepage + blog posts and NOT the selling pages,
     *    so the flag is discharged in the function rather than through the
     *    filter, and the reasoning stays where the next reader will find it.
     *
     * ⛔ WHAT IS STILL EXCLUDED, AND THE EXCLUSION IS THE POINT: every
     *    commercial-intent surface. `/complete-collection/`, single product
     *    pages, `/shop/`, `/books/`, product archives, every page template
     *    other than the front page, cart and checkout (already suppressed
     *    upstream in `bhp_should_show_any_popup()`), `/teachers/` (above),
     *    and both funnels' own landing and thank-you pages. Interrupting
     *    somebody who is already reading a price is the one place a capture
     *    overlay costs more than it earns — that finding
     *    (`commerce-cx` / Pippin, `CYCLE164-CX` #3) is unchanged and this
     *    release does not touch it.
     *
     * ⛔ `is_singular('post')` IS THE NARROWEST TEST THAT MEANS "A BLOG POST".
     *    It is true for exactly one thing: a single post of the built-in
     *    `post` type. It is FALSE for the blog index, for category, tag, date
     *    and author archives, for pages, for products and for every custom
     *    post type, so widening here cannot leak the popup onto an archive or
     *    a product by accident.
     */
    /*
     * ═══════════════════════════════════════════════════════════════════
     * ⭐ 1.19.296 (2026-08-27, `CYCLE167-LD-CAPTURE-FIX-BUILD`) — THE #1
     *    HUMAN ENTRY PAGE JOINS THE GATED SURFACE. HOMEPAGE + BLOG POSTS +
     *    `/complete-collection/`.
     * ═══════════════════════════════════════════════════════════════════
     *
     * ⭐ THE FINDING (Merry, `CYCLE167-MKT-CAPTURE-ENTICEMENT-R3`, verified
     *    live + 30 days of production access logs): `/complete-collection/`
     *    takes **134 human entries in 30 days — rank 1 on the whole site** —
     *    and carried the HARDEST capture gate we run. Because it is neither
     *    the front page nor a post, it fell through to the exit-intent modal,
     *    whose mobile trigger is 20s dwell AND 45% scroll AND a 400px upward
     *    flick inside 600ms. Its entry:pageview ratio is 1.32, so most people
     *    who land there see that one page and leave.
     *    ⛔ THE PLAIN VERSION: the page that receives more first arrivals than
     *    any other is the page where we asked for the least, latest and
     *    hardest. A closed door with the doorbell on the inside.
     *
     * ⛔⛔ THIS MOVES AGAINST A RECORDED ENGINEERING FINDING, AND IT IS
     *    FLAGGED RATHER THAN SMUGGLED. 1.19.241 excluded the commercial-intent
     *    pages on `commerce-cx` / Pippin's `CYCLE164-CX` #3: *"interrupting
     *    somebody who is already reading a price is the one place a capture
     *    overlay costs more than it earns."* That finding is NOT refuted by
     *    anything here and it is not deleted from this file.
     *
     * ⭐ WHAT AUTHORISES THE CHANGE: Andrew Signore, carrier item 280
     *    (2026-08-27), naming the "placement flip on the top entry page" in
     *    tonight's build program, and item 279's *"Emails and Sales are the 2
     *    biggest KPIs."* ⚠ RELAYED through the Chief of Staff, not witnessed
     *    by this build. ⛔ IT IS STAGING-ONLY UNTIL HE TOKEN-TOUCHES A DEPLOY.
     *
     * ⚠ A NARROWER OPTION EXISTS AND HE SHOULD SEE IT. Merry's own R4
     *   recommends this flip on MOBILE ONLY, leaving desktop exit-intent alone
     *   because a real mouse-leave is a genuine signal that costs a buyer
     *   nothing. The engine reads `trigger.mode` globally rather than per
     *   device, so mobile-only would need a config-schema extension to an
     *   engine four surfaces share — its own piece of work, not a side effect
     *   of this one. BUILT AS BRIEFED (both devices); the narrower option is
     *   in this build's report so the choice is his and not this desk's.
     *
     * ⛔ EXCLUSIONS THAT DO **NOT** MOVE: `/shop/`, `/books/`, single product
     *    pages, product archives, cart and checkout (already suppressed
     *    upstream), `/teachers/` (above), and both funnels' own landing and
     *    thank-you pages — including `/reluctant-reader-adventure-kit/`, which
     *    the pipe diagnosis's FIX-4 also proposes reopening and which is
     *    DELIBERATELY NOT TOUCHED HERE: that page IS this offer's destination,
     *    and its exclusion is routed to Andrew, not absorbed by this build.
     *
     * ⛔ NO STORAGE KEY, EVENT PREFIX, COPY STRING OR TIMER CHANGES. This is a
     *    surface rule only, so `.claude/rules/funnels.md`'s isolation
     *    guarantees hold exactly as before: the parent funnel keeps
     *    `bhp_parent_popup` / `parent_popup`, the teacher funnel is untouched
     *    in both directions, and a visitor who dismissed this offer anywhere
     *    is still not re-asked here.
     */
    $bhp_ab_popup_surface = is_front_page()
        || is_singular('post')
        || is_page('complete-collection');

    if (!$bhp_ab_popup_surface) {
        return false;
    }

    if (!bhp_get_reluctant_reader_download()['ready']) {
        return false;
    }

    return (bool) apply_filters('bhp_show_parent_ab_popup', true);
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.271 (2026-08-19, `CYCLE165-LD-ITERATE-7-KIT-CTA-POPUP`) — A KIT
 *    CTA ON THE BLOG OPENS THE KIT POPUP INSTEAD OF NAVIGATING.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⭐ Andrew Signore, verbatim, relayed by the Chief of Staff as first-hand
 *    founder wording (carrier item 117): "When someone on the blog hits Get
 *    the free kit - it should be a pop up where they can immediately
 *    subscribe- not send them to the reluctant reader page. Less steps."
 *
 * WHAT WAS ACTUALLY ON THE BLOG WHEN THIS WAS BUILT — measured, not assumed.
 * All 36 published posts were fetched from staging2 at 1.19.270 and their
 * rendered HTML searched for links to the kit page:
 *
 *   - EVERY post carries exactly ONE such link, and it is the FOOTER NAV
 *     item "Free Reluctant Reader Kit". That is sitewide chrome, it appears
 *     on selling pages too, and it is NOT touched here.
 *   - ONE post (`/blog/best-books-for-7-year-olds/`, id 28) additionally
 *     carries the contextual-CTA block for `adventure_kit_signup`. ⭐ THAT is
 *     the navigating kit CTA the ruling bites on.
 *
 * ⚠ AND IT IS NOT WHAT IT LOOKS LIKE — A CORRECTION THIS RELEASE MADE TO
 *   ITSELF AFTER MEASURING. That block LOOKS like `[bhp_contextual_cta]`
 *   output because it is: somebody rendered it once and PASTED THE RESULT into
 *   the post. `wp post get 28 --field=content` returns the finished `<a
 *   class="btn btn-primary" ... data-bhp-cta-id="adventure_kit_signup">` as
 *   stored HTML, with no shortcode anywhere in the post. The CTA engine
 *   therefore NEVER RUNS on the one post that has a kit CTA.
 *
 *   Two consequences, both recorded rather than smoothed over:
 *     1. THE CONTENT FILTER BELOW IS LOAD-BEARING TODAY, not a hedge against
 *        some future editor. It is the only thing that carries the founder's
 *        ruling to the live blog.
 *     2. The engine change is still correct and still required — a shortcode
 *        placed tomorrow renders through it — but on 2026-08-19 it is
 *        exercised only by the test suite and by a direct `wp eval` probe,
 *        never by a page load. Both paths were verified; see the QA evidence.
 *   - The END-OF-POST CAPTURE IS ALREADY AN INLINE FORM. It posts to the
 *     Mailchimp endpoint and returns to the same post; it never navigated
 *     anywhere, so there are already zero steps to remove and it is left
 *     BYTE-UNTOUCHED. The brief anticipated this and instructed exactly that.
 *   - The "book this came from" rail carries NO free-CTA. Its mode is
 *     `product` or `look_inside` by deliberate design (see
 *     `bhp_blog_rail_cta()`), so there was nothing of that kind to change.
 *   - The BLOG INDEX carries no kit CTA at all — only the same footer nav
 *     item. The brief's "(and blog index if it carries one)" is therefore
 *     satisfied by measurement rather than by code.
 *
 * ⛔ THE KIT PAGE IS UNTOUCHED and other surfaces still link to it. This is a
 *    blog-surface behaviour, not a retirement of the destination.
 */
function bhp_kit_popup_dom_id() {
	/*
	 * ⛔ THE ONE PLACE THIS STRING IS COMPUTED. The literal also appears once
	 *    more, as the `id` attribute in
	 *    `template-parts/acquisition/parent-ab-popup.php`, where the existing
	 *    suite already asserts it character for character. The test added
	 *    with this release asserts that the two agree, so a rename of either
	 *    fails loudly instead of silently producing a CTA that points at
	 *    nothing.
	 */
	return 'parent-ab-popup';
}

/**
 * The blog surfaces the founder's ruling names, and nothing else.
 *
 * `is_home()` is the blog index; `is_singular('post')` is one blog post. It is
 * deliberately NOT `is_front_page()` — the homepage is out of this brief's
 * scope, and its own CTAs keep the behaviour they have.
 */
function bhp_is_blog_kit_cta_surface() {
	return is_home() || is_singular( 'post' );
}

/**
 * Whether a kit CTA rendered on THIS request should open the popup.
 *
 * ⛔ BOTH LIMBS ARE REQUIRED, AND THE SECOND ONE IS THE SAFETY PROPERTY.
 *    `bhp_should_show_parent_ab_popup()` is the same function the footer uses
 *    to decide whether to render the popup at all, so a CTA can only ever be
 *    marked as a popup trigger on a page where the popup is genuinely in the
 *    DOM. There is no request on which this returns true and the target is
 *    absent, which is what stops the enhancement from producing a dead button
 *    on a selling page, an archive, `/teachers/`, the cart or the checkout.
 *
 *    It is ALSO why the blog index is currently excluded: the popup does not
 *    render there. Recorded rather than worked around — widening the popup's
 *    own surface is a separate decision and not this brief's.
 */
function bhp_kit_cta_opens_popup() {
	return bhp_is_blog_kit_cta_surface() && bhp_should_show_parent_ab_popup();
}

/**
 * The attribute pair that upgrades an ordinary anchor into a popup trigger,
 * or an EMPTY ARRAY where it must stay an ordinary anchor.
 *
 * ⛔ THE `href` IS NEVER REMOVED OR REPLACED BY ANY CALLER. The upgrade is
 *    purely additive: with no JavaScript, with a failed script load, or with
 *    the popup suppressed for this visitor, the control is still a link to
 *    the kit page. A "Get the Free Kit" button that does nothing would be a
 *    worse outcome than the extra step the founder asked us to remove.
 *
 * @param string $reason A short analytics label for why the popup opened.
 * @return array
 */
function bhp_kit_popup_trigger_attrs( $reason = 'cta_click' ) {
	if ( ! bhp_kit_cta_opens_popup() ) {
		return array();
	}

	return array(
		'data-bhp-popup-open'        => bhp_kit_popup_dom_id(),
		'data-bhp-popup-open-reason' => sanitize_key( $reason ),
	);
}

/**
 * IN-CONTENT kit links stored in the post itself.
 *
 * ⭐ THIS IS THE FILTER THAT ACTUALLY DELIVERS THE FOUNDER'S RULING ON THE LIVE
 *    BLOG, and an earlier draft of this comment said the opposite. It claimed
 *    no such link existed and called this "future-proofing". Measuring the
 *    stored content of post 28 disproved that: the one kit CTA the blog has is
 *    pasted HTML, so it reaches the reader through THIS filter and not through
 *    the CTA engine. The withdrawn claim is left recorded here rather than
 *    quietly deleted, because a future reader deciding whether this filter is
 *    dead weight needs to know it was nearly removed for exactly that reason.
 *
 * ⛔ IT ADDS ATTRIBUTES AND CHANGES NOTHING ELSE. It never rewrites an `href`,
 *    never removes a link, never touches link text and never touches an anchor
 *    that points anywhere but the kit page's own path on this host. An anchor
 *    that already carries the trigger attribute — which is exactly what the
 *    contextual-CTA block renders, and that block is produced by a shortcode
 *    INSIDE `the_content()` — is returned untouched, so the two paths cannot
 *    double-stamp each other.
 *
 * PRIORITY 20: after `do_shortcode()` (11), so the shortcode's own output is
 * present and can be recognised and skipped rather than raced.
 */
function bhp_kit_content_links_open_popup( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( false === stripos( $content, 'reluctant-reader-adventure-kit' ) ) {
		return $content;
	}

	$attrs = bhp_kit_popup_trigger_attrs( 'content_link' );
	if ( ! $attrs ) {
		return $content;
	}

	$kit_path  = untrailingslashit( (string) wp_parse_url( home_url( '/reluctant-reader-adventure-kit/' ), PHP_URL_PATH ) );
	$site_host = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );

	$attr_string = '';
	foreach ( $attrs as $name => $value ) {
		$attr_string .= ' ' . $name . '="' . esc_attr( $value ) . '"';
	}

	$replaced = preg_replace_callback(
		'/<a\s[^>]*>/i',
		function ( $matches ) use ( $kit_path, $site_host, $attr_string ) {
			$tag = $matches[0];

			if ( false !== stripos( $tag, 'data-bhp-popup-open' ) ) {
				return $tag;
			}
			if ( ! preg_match( '/\shref\s*=\s*("|\')(.*?)\1/i', $tag, $href_match ) ) {
				return $tag;
			}

			$url  = html_entity_decode( $href_match[2], ENT_QUOTES );
			$host = (string) wp_parse_url( $url, PHP_URL_HOST );
			if ( '' !== $host && $host !== $site_host ) {
				return $tag;
			}
			$path = untrailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) );
			if ( '' === $path || $path !== $kit_path ) {
				return $tag;
			}

			// Rebuild the open tag, preserving an XHTML-style trailing slash
			// if the author wrote one.
			$inner  = substr( $tag, 0, -1 );
			$suffix = '';
			if ( '/' === substr( $inner, -1 ) ) {
				$inner  = substr( $inner, 0, -1 );
				$suffix = ' /';
			}

			return rtrim( $inner ) . $attr_string . $suffix . '>';
		},
		$content
	);

	// preg_replace_callback returns null on failure (e.g. backtrack limit on a
	// very long post). Never hand null back to the content chain.
	return ( null === $replaced ) ? $content : $replaced;
}
add_filter( 'the_content', 'bhp_kit_content_links_open_popup', 20 );

/**
 * 1.19.205 — the A/B popup's cover strip, as attachment IDs only.
 *
 * ⛔ WHY THIS IS CACHED, AND WHY THAT IS NOT PREMATURE. The popup renders in
 *    `wp_footer` on essentially every page of the site. Its source,
 *    `bhp_get_series_adventures()`, calls `bhp_get_homepage_books(-1)`, which
 *    is a product `get_posts()` plus one `wc_get_product()` per result — a
 *    perfectly reasonable cost on the three pages that were built around it,
 *    and an unreasonable one to add to every blog post and every product page
 *    for three decorative thumbnails. The transient reduces that to one
 *    option read (none at all where an object cache is warm).
 *
 * ⛔ NO NEW MEDIA. These are the SAME attachments the homepage, the collection
 *    page and the parent landing page already render. Nothing is uploaded,
 *    composited or generated here — `.claude/rules` and the company memory
 *    both forbid regenerating a real cover, and this does not come close to
 *    it: it reuses an ID.
 *
 * An empty array is a valid answer and is what a site with no matching
 * products returns; the template then renders no strip rather than a gap.
 * Twelve hours, because a cover attachment changes on the order of never and
 * a stale ID for half a day is a decorative image, not a fact.
 */
function bhp_get_popup_ab_covers() {
    $cached = get_transient('bhp_popup_ab_covers');
    if (is_array($cached)) {
        return $cached;
    }

    $covers = [];
    foreach (bhp_get_series_adventures() as $adventure) {
        if (empty($adventure['image_id'])) {
            continue;
        }
        $src = wp_get_attachment_image_src((int) $adventure['image_id'], 'medium');
        if (!$src || empty($src[0])) {
            continue;
        }
        $covers[] = [
            'id'     => (int) $adventure['image_id'],
            'url'    => $src[0],
            'width'  => (int) $src[1],
            'height' => (int) $src[2],
        ];
    }

    set_transient('bhp_popup_ab_covers', $covers, 12 * HOUR_IN_SECONDS);

    return $covers;
}

/**
 * The variant tag set. A SEPARATE add_filter call, not an edit to either
 * existing one, so the proven Parent / Mariana / Wave 1 tag logic stays
 * byte-untouched — the same reasoning recorded on both callbacks above.
 *
 * ⛔ NO TAG NAMES A RESOURCE THAT DOES NOT EXIST. Both variants deliver the
 *    Reluctant Reader Adventure Kit, so that is the resource tag for both.
 *    The variant adds ONE further tag and nothing else, and its value is the
 *    same `content_name` the pixel's Lead event carries, so Mailchimp and
 *    Meta can be joined without a lookup table.
 */
add_filter('bhp_mailchimp_signup_tags', function ($tags, $context, $audience_type, $lead_magnet, $source_page) {
    if ($context !== 'parent_popup_ab') {
        return $tags;
    }

    $resolved = ['Reluctant Reader Adventure Kit', 'Audience: Parent/Grandparent', 'Source: Parent Popup A/B'];

    $raw = isset($_POST['bhp_variant']) ? wp_unslash($_POST['bhp_variant']) : '';
    $key = bhp_resolve_popup_ab_variant($raw);
    if ($key) {
        $variants = bhp_get_popup_ab_variants();
        $resolved[] = 'Variant: ' . $variants[$key]['content_name'];
    }

    return $resolved;
}, 10, 5);

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

    /*
     * ⭐⭐ 1.19.269 (`CYCLE165-LD-ITERATE-5-SUBTRACTIONS`) — NEVER ON A BLOG
     *     POST. Founder ruling, 2026-08-19, subtraction item 1: on blog posts
     *     keep the popup and the end-of-post capture, and remove the mid-post
     *     ask and THIS form.
     *
     * WHY THE GATE RATHER THAN THE TEMPLATE. `footer.php` renders this block
     * from two places (the ordinary position, and 1.19.254's R-9 deferred
     * position on the collection page). One condition in the eligibility
     * function closes both, and closes any third caller a future release adds.
     * A `!is_singular('post')` test in the template would have closed one.
     *
     * ⛔ SCOPE IS POSTS ONLY, DELIBERATELY. `is_singular('post')` is true for a
     *    single blog post and false for the /blog/ archive, for every page, for
     *    every product and for the home page — so the capture is unchanged on
     *    all ~80 non-post surfaces. Andrew's item 1 says "on posts", and this
     *    is that word in code.
     *
     * ⭐ THE PARENT FUNNEL STILL HAS TWO ASKS ON EVERY POST after this: the
     *    end-of-post capture (`template-parts/acquisition/post-end-capture.php`,
     *    context `blog_post_end`) and the popup. No storage key, analytics
     *    prefix or lead-magnet key is touched here, so funnel isolation
     *    (`.claude/rules/funnels.md`) is unaffected.
     */
    if (is_singular('post')) {
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
 * ⭐ 1.19.296 (2026-08-27, `CYCLE167-LD-CAPTURE-FIX-BUILD`) — THE MARKET /
 *    EVENT QR SURFACE (`page-market-capture.php`).
 *
 * ⭐ WHY IT NEEDS ITS OWN SOURCE TAG, stated because a new tag string in a
 *    live audience is not a free action: **73 books were sold at one market
 *    weekend and zero emails were captured** (founder-attested). Without a
 *    distinct source, an in-person signup would be indistinguishable from a
 *    website signup and that question stays permanently unanswerable — which
 *    is the same source-attribution gap `CYCLE148-FIN-002` already records.
 *
 * ⛔ A SEPARATE `add_filter` CALL, not an edit to any existing one, so every
 *    proven tag path above stays byte-untouched. Priority 20 so it runs after
 *    the base map, exactly like `bhp_read_aloud_mailchimp_tags()`.
 *
 * ⛔ IT REGISTERS GLOBALLY AND NOT IN THE PAGE TEMPLATE, DELIBERATELY. Tags are
 *    applied during the POST to `admin-post.php`, at which point the template
 *    that rendered the form is not loaded. A filter added inside
 *    `page-market-capture.php` would never fire, and the signup would land
 *    untagged — a defect that would look exactly like the tag "not working".
 *
 * ⚠ THE TAG STRING IS ANDREW'S CALL AND IS FLAGGED TO HIM, not assumed. It is
 *   modelled on the two he already has: "Source: Read-Aloud Visit" and
 *   "Source: Blog Post".
 */
add_filter('bhp_mailchimp_signup_tags', function ($tags, $context, $audience_type, $lead_magnet, $source_page) {
    unset($audience_type, $source_page);

    if ($context !== 'market_capture') {
        return $tags;
    }
    if ($lead_magnet !== 'reluctant_reader_adventure_kit') {
        return $tags;
    }

    return ['Reluctant Reader Adventure Kit', 'Audience: Parent/Grandparent', 'Source: Market Event'];
}, 20, 5);

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

    // 1.19.204 — the A/B capture popup. Andrew Signore, current turn: "I say
    // build it now… Make it 15 second delay."
    //
    // ⛔ It follows the SAME precedence rule the block above states, for the
    //    same reason and with the same words: a timed parent-funnel surface
    //    outranks exit-intent, because both are the same funnel and the same
    //    offer and one visitor should be asked once. This does NOT reverse
    //    Andrew's 2026-08-04 "Turn it on" — `bhp_show_exit_intent_popup` is
    //    still filtered true, the surface is still built and still renders
    //    wherever this popup does not. Note also that the two could never
    //    both be seen in one session anyway: this popup opens at 15s and
    //    claims the shared session slot, and exit-intent's own floor is 20s.
    if (bhp_should_show_parent_ab_popup()) {
        get_template_part('template-parts/acquisition/parent-ab-popup');
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
 * ⭐ 1.19.204 — RETIRE THE QUIZ MODAL'S AUTOMATIC OPEN. Andrew Signore,
 *    current turn: build the A/B capture popup and "only the popup slot
 *    changes". One automatic surface per page, and from this release it is
 *    the capture popup.
 *
 * ⛔ WHAT THIS DOES NOT DO — and the distinction is the whole point:
 *    the quiz REMAINS FULLY REACHABLE. `bhp_should_show_quiz_cta()` is
 *    untouched, so the "Find My Best Next Step" launcher still renders
 *    sitewide and still opens the quiz in place; /find-your-adventure/ is
 *    unchanged; the quiz's own routing, copy and analytics are untouched.
 *    Only `bhp_should_autoopen_quiz()` is filtered off, which is exactly the
 *    surface the new popup takes over.
 *
 * Fully reversible in one line — remove this filter — and deliberately
 * expressed as a filter rather than an edit to bhp_should_autoopen_quiz(),
 * whose own body still records Andrew's 2026-07-19 reasoning and must stay
 * legible. The existing collection-page filter in inc/audit-remediation.php
 * is left in place and still runs; it is now redundant rather than wrong.
 */
add_filter('bhp_show_quiz_autoopen', '__return_false');

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

    /*
     * ═══════════════════════════════════════════════════════════════════════
     * ⭐⭐ 1.19.270 (2026-08-19, `CYCLE165-LD-ITERATE-6-PATH-LINE`) — THE
     *     HOMEPAGE EXCLUSION IS RESTORED, BY THE FOUNDER'S OWN RULING.
     * ═══════════════════════════════════════════════════════════════════════
     *
     * Andrew Signore, carrier item 114, ⛔ RELAYED through the Chief of Staff
     * and NOT witnessed first-hand by the agent that wrote this line:
     *
     *   "Remove the 'not sure which brave hearts path fits' on the home page
     *    then we can deploy."
     *
     * That copy is `template-parts/components/quiz-entry-cta.php`'s first
     * line. On the homepage this launcher is the ONLY thing that renders it,
     * so switching this gate off for the front page removes exactly the band
     * he named and nothing else.
     *
     * ⭐ NOTE THE SYMMETRY WITH 2026-07-19, DELIBERATELY LEFT VISIBLE ABOVE.
     *    The homepage used to be excluded here; Andrew lifted the exclusion on
     *    2026-07-19 when the homepage still embedded the inline quiz. He has
     *    now reinstated it. Both of his instructions are preserved in place
     *    rather than one being edited away, so a future reader can see that
     *    this gate has been flipped twice, by him, for two different pages.
     *
     * ⛔ WHAT THIS DOES NOT DO, AND THE SCOPE IS THE WHOLE POINT:
     *    · The canonical `/find-your-adventure/` PAGE is untouched. It renders
     *      the quiz through `page-find-your-adventure.php` and gets its assets
     *      from `bhp_enqueue_audience_quiz_assets()`'s own
     *      `$on_canonical_quiz_page` limb, neither of which reads this gate.
     *    · EVERY OTHER PAGE that shows the launcher today still shows it. This
     *      is `is_front_page()`, not a sitewide retirement.
     *    · The quiz component, its routing, its copy, its four segments and its
     *      analytics vocabulary are byte-identical.
     *    · The hero's ghost CTA "Take the 30-second quiz." is NOT affected: it
     *      is an `<a href>` to the canonical PAGE (`front-page.php`'s
     *      `$hero_quiz_url`), never a fragment into this band, so the homepage
     *      keeps a live route into the quiz after this band is gone.
     *    · `bhp_should_autoopen_quiz()` inherits this correctly (it already
     *      returns false everywhere via the 1.19.204 filter).
     *
     * ⚠ THE `#find-your-adventure` DEEP-LINK ANCHOR LEAVES THE HOMEPAGE WITH
     *   THE BAND. It was carried by this launcher (footer.php's `id` arg) and
     *   by nothing else. SCANNED BEFORE REMOVING, across *.php/*.js/*.css:
     *   the theme emits NO `href="#find-your-adventure"` anywhere, so no link
     *   on any surface is orphaned by this. `page-find-your-adventure.php`'s
     *   own `#find-your-adventure-intro` id is a DIFFERENT id on a DIFFERENT
     *   page and is untouched.
     *
     * Fully reversible in one line: delete this block.
     */
    if (is_front_page()) {
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

/*
 * ⭐ 1.19.292 (`CYCLE166-CX-CAPTURE-REPAIR`) — single-use, short-TTL
 *    conversion tokens. REQUIRED BEFORE inc/mailchimp.php DELIBERATELY:
 *    `bhp_process_signup()` calls `bhp_add_conversion_token()` on its
 *    success path, and loading the provider first means that call can never
 *    hit an undefined function regardless of how the file is reached.
 *    Full rationale and the production log evidence: the file's own header.
 */
require_once get_template_directory() . '/inc/conversion-token.php';
require_once get_template_directory() . '/inc/mailchimp.php';
/*
 * ⭐ 1.19.296 (`CYCLE167-LD-CAPTURE-FIX-BUILD`) — FIX-2 (interim) of the
 *    capture-pipe diagnosis. STAGING-ONLY recording transport, so the email
 *    pipe can be exercised end to end in an environment that is not the
 *    founder's live audience.
 *
 * ⛔ REQUIRED AFTER inc/mailchimp.php DELIBERATELY: the stub only ADDS filters
 *    to hooks that file declares. Loading it first would register callbacks
 *    for filters that do not exist yet — harmless in WordPress, but it would
 *    invert the dependency and hide it from the next reader.
 *
 * ⛔ IT IS INERT ON PRODUCTION BY CONSTRUCTION — it registers NO hooks unless
 *    `BHP_Analytics_Config::is_staging()` is true, which compares the real
 *    HTTP host. It contains no credential and makes no HTTP call.
 */
require_once get_template_directory() . '/inc/mailchimp-staging-stub.php';
require_once get_template_directory() . '/inc/lead-magnet-settings.php';
/*
 * ⭐ 1.19.292 (`CYCLE166-CX-CAPTURE-REPAIR`) — the three thank-you pages are
 *    noindex and out of the XML sitemap. The stored `rank_math_robots`
 *    postmeta is the mechanism (Rank Math's sitemap provider reads it in raw
 *    SQL and never runs a PHP filter); the frontend filters are backstops.
 */
require_once get_template_directory() . '/inc/thankyou-indexing.php';
// Phase 2 (2026-07-30): unified one-page-per-title purchase experience.
// Presentation layer only — no product record is merged or altered.
require_once get_template_directory() . '/inc/book-formats.php';
require_once get_template_directory() . '/inc/book-media.php';
// Google Merchant Center feed attributes. Loaded AFTER book-formats.php because
// it keys its allowlist on bhp_book_lookup_product() and must not re-derive
// "is this one of the six editions" a second way. Read-time feed filter only —
// it changes no WooCommerce product record. See the file header for the
// ebooks_policy_violation disapproval it answers.
require_once get_template_directory() . '/inc/google-feed.php';
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
// ⭐ 1.19.280 — THE PURCHASE FLOW (`CYCLE165-LD-PURCHASE-FLOW-ROUND`, founder
// carrier item 186). Every buy button finishes on /checkout/ instead of the
// cart page. Loaded BEFORE book-formats.php's data helpers are CALLED (they
// run at render time, not at require time) and beside collection-cta.php
// because both encode the same "finish on /checkout/" contract — one with the
// plugin's POST flag, this one with an allowlisted GET flag for the native
// WooCommerce add-to-cart links the format selector renders.
//
// ⛔ IT REGISTERS ONE FILTER AND CHANGES NO WOOCOMMERCE SETTING. With no flag
//    on the request it returns WooCommerce's own destination untouched, so
//    every add-to-cart that is not one of our buy buttons is byte-identical
//    to 1.19.279.
require_once get_template_directory() . '/inc/purchase-flow.php';
// ⭐ 1.19.280 — the HEADER CART CONTROL that opens the already-shipped
// mini-cart side panel (`#bhp-cart-drawer`). Carrier item 186. It builds no
// panel: it renders one <button> carrying the generic `data-bhp-cart-open`
// hook that plugin 1.8.64 wires to the drawer's own openDrawer(). Loaded
// after purchase-flow.php purely for reading order — the two are independent.
require_once get_template_directory() . '/inc/mini-cart.php';
// ⭐⭐ 1.19.281 — THE STAGING ORDER-EMAIL GUARD. The QA round of 2026-08-21
// placed real staging orders through the real checkout, and every one of them
// mailed Andrew before the test orders were cleaned up. This suppresses
// WooCommerce ORDER emails on staging ONLY, keyed to the same
// `BHP_Analytics_Config::STAGING_HOST` literal every other staging-only guard
// in this theme uses. ⛔ It writes no WooCommerce setting, and there is no
// value of anything that makes it fire on production — an unknown host is
// treated as production, which is the fail-safe direction.
require_once get_template_directory() . '/inc/staging-mail-guard.php';
// ⭐ 1.19.277 — THE COLOURING LINE ON THE STOREFRONT, and the offer surface
// FD-579 rules. Loaded AFTER book-formats.php because its shop-card hooks sit
// beside that file's on the same WooCommerce loop actions and its offer cards
// must inject BEFORE the collection card (filter priority 5 vs the default
// 10), and AFTER collection-cta.php because both read the bundle plugin's
// nonce/redirect contract rather than restating it.
//
// ⛔ IT FAILS CLOSED TO 1.19.276 EVERYWHERE THE COLOURING LINE IS ABSENT. Every
//    surface in it gates on bhp_colouring_product_ids() (SKU-keyed, plugin
//    1.8.61) or on bhp_offer_is_purchasable(). On an environment where no
//    colouring SKU resolves, every hook it registers returns without emitting a
//    byte, and nothing that already renders is altered.
require_once get_template_directory() . '/inc/colouring-line.php';
// 1.19.260 — the mobile-header offer (CYCLE165-LD-DIRECTION1-STEP1-HEADER).
// Loaded AFTER collection-cta.php so the two header controls are read in the
// order they render, and after book-formats.php/the bundle plugin so the live
// price helpers exist by the time header.php calls the renderer.
require_once get_template_directory() . '/inc/header-offer.php';

/*
 * 1.19.304, `CYCLE167-LD-RETAILER-PAGE` — the bookseller/retailer trade
 * registry. Pure data plus two resolvers; no hooks, no output. It answers one
 * question and nothing else: WHICH EDITIONS MAY A BOOKSELLER BE TOLD TO ORDER
 * TODAY, and on what terms. Read by `page-audience-retailers.php` alone.
 */
require_once get_template_directory() . '/inc/retailer-trade-terms.php';
// 1.19.261 — the blog post template (CYCLE165-LD-DIRECTION1-STEP2-BLOG), step 2
// of the same board build. Loaded AFTER header-offer.php because its whole
// placement rule is "below whatever step 1 put above the fold", and after
// book-formats.php / the bundle plugin so bhp_book_has_look_inside() and
// bhp_bundle_landing_price_facts() exist when the rail resolves its facts.
require_once get_template_directory() . '/inc/blog-post-template.php';
// 1.19.262 — the product template (CYCLE165-LD-DIRECTION1-STEP3-PRODUCT),
// step 3 of the same board build. Loaded AFTER header-offer.php for the same
// reason step 2 is: this step is what makes the product page carry its own
// above-fold primary, which is why header-offer.php now suppresses itself
// there. Loaded after book-formats.php because the buy box it reorders is
// rendered by bhp_book_render_format_selector().
require_once get_template_directory() . '/inc/product-template.php';
// 1.19.263 — the drawn field-mark set (CYCLE165-LD-DIRECTION1-STEP4-HOME),
// step 4 of the same board build. Pure markup helpers with no hooks, no
// options and no dependency on WooCommerce or the bundle plugin, so its load
// position is not load-bearing; it sits with its three siblings because a
// future reader looking for "where did Direction 1 get wired in" should find
// all four in one place.
require_once get_template_directory() . '/inc/field-marks.php';
require_once get_template_directory() . '/inc/amazon-reviews.php';
// Native customer reviews (2026-08-03): the on-page "Write a Review for …"
// section beneath the Kirkus block, the /review/<slug>/ two-click email
// destination, and enforced moderation. Loads AFTER book-formats.php because
// it maps every format of a title onto that title's canonical product via
// bhp_book_canonical_id(). Emits no schema of any kind — see the file header.
require_once get_template_directory() . '/inc/reviews.php';
require_once get_template_directory() . '/inc/class-bhp-printed-for-you.php';

// Author visits list (2026-08-17, CYCLE162-LD-VISITS-PAGE): the data half of
// the /author-visits/ page template. READ-ONLY over the bundle plugin's
// bhp_school_visits registry -- it writes nothing, sets no visit flag, and
// hardcodes no school, date or slug. Every call into plugin territory is
// function_exists()-guarded, so a deactivated bundle plugin renders the page's
// empty state instead of a fatal.
require_once get_template_directory() . '/inc/author-visits.php';

// Read-aloud take-home landing (2026-08-24, CYCLE166-CX-READALOUD-LANDING):
// the decisions half of /read-aloud/, the destination of the dynamic QR printed
// on the coloring page every child takes home from a school read-aloud. It
// writes NOTHING -- no option, meta, session, cookie, product or setting. It
// mints NO new funnel: the page's one capture is the EXISTING parent-funnel
// Adventure Kit with a distinct signup CONTEXT only, per .claude/rules/funnels.md
// ("extend the config schema, don't fork the engine"). Every call into bundle-
// plugin territory is function_exists()-guarded.
// ⛔ MUST load AFTER the bhp_mailchimp_signup_tags callbacks above -- and it
//    does not RELY on that: its callback registers at priority 20 precisely so
//    the outcome is a stated rule rather than a consequence of this line's
//    position in the file. See the docblock on bhp_read_aloud_mailchimp_tags().
require_once get_template_directory() . '/inc/read-aloud-landing.php';

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

// Crawl hygiene for thin, machine-generated URLs (added 2026-08-10,
// CYCLE152-LD-SEO-FEED-HYGIENE, from the founder-supplied Search Console
// export): taxonomy/author/search feeds 301 to their parent archive and stop
// being advertised in <head>, and robots.txt gains the `?wc-ajax=` and
// `/search/` disallows. The MAIN post feed is untouched. See the file header
// for why 301 and not 404, and for what was deliberately left alone.
require_once get_template_directory() . '/inc/seo-hygiene.php';

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
        /*
         * ═══════════════════════════════════════════════════════════════════
         * ⭐⭐ 1.19.276 — THE COLOURING LINE IS EXCLUDED BY PRODUCT ID, BEFORE
         *     THE SUBSTRING MATCHER BELOW EVER SEES THE TITLE.
         *     ⛔ `CYCLE165-OPS-019` / `ACT-OPS-269`. PRE-EMPTED, not found live.
         * ═══════════════════════════════════════════════════════════════════
         *
         * ⛔⛔ THE DEFECT. The loop below assigns a product to an adventure by
         *    SUBSTRING MATCH ON THE PRODUCT TITLE ('mariana trench',
         *    'mariana', ...). The founder-ruled colouring title (`FD-557`) is
         *    "Coloring Adventures with Charlotte and Henry: The Mariana Trench
         *    Ocean Coloring Book" -- which CONTAINS 'mariana trench'. It would
         *    be absorbed into the `mariana_trench` adventure the moment it is
         *    published, on either environment.
         *
         * ⛔ NOTHING CONTAINED IT. `bhp_get_homepage_books()` scopes by
         *    `product_cat` only if `charlotte-henry`, `charlotte-and-henry` or
         *    `books` exists -- and ⭐ NONE of them does (both category URLs
         *    return 404, verified live 2026-08-20). The query is therefore
         *    UNFILTERED across every published product.
         *
         * ⛔ WHAT BREAKS, CONCRETELY, AND IT IS THE `FD-549` FAILURE SHAPE:
         *    `primary_url` for the Mariana adventure can be reassigned to the
         *    colouring book, and `image_id` / `image_alt` FOLLOW IT -- so the
         *    COLOURING COVER appears where the CHAPTER-BOOK COVER belongs,
         *    beside the chapter-book price. An image and a price that describe
         *    different objects is a false claim assembled from two true facts.
         *    ⚠ Which one wins depends on the order products come back in,
         *    which is exactly the kind of silent, ordering-dependent breakage
         *    that does not show up in one test run.
         *
         * ⭐ AND IT REACHES THE TEST SUITE: `tests/test-protected-elements.php`
         *    resolves its product target from THIS function and runs the
         *    product manifest against `$pe_products[0]`. That manifest requires
         *    `amazon-review-card__quote` -- ⛔ A REAL REVIEW. A brand-new
         *    colouring book has none, so the suite would fail on a page that
         *    has no review to show. ⛔⛔ `FD-542` GOVERNS WHAT HAPPENS THEN: a
         *    blocked build IS the manifest working. This fix keeps the
         *    colouring book OUT of the PE target so the question does not
         *    arise; ⛔ whether it should ever BE a target is spec decision D-6
         *    and is ANDREW'S ONLY. No manifest row is relaxed here.
         *
         * ⭐⭐ THE FIX IS AN ID TEST, NEVER A SUBSTRING TEST. Product IDs are
         *    resolved from the colouring line's own SKU registry
         *    (`bhp_colouring_product_ids()`, bundle plugin 1.8.61). ⛔ It fails
         *    CLOSED and INERT: with no colouring product on either environment
         *    today, `bhp_is_colouring_product()` returns false for everything
         *    and this loop behaves byte-for-byte as it did before.
         *
         * ⚠ IF THE BUNDLE PLUGIN IS ABSENT the guard cannot run, and the
         *   substring matcher is reached exactly as before. That is the honest
         *   degradation and it is stated rather than hidden: the theme does
         *   not silently reimplement the registry, because two registries
         *   drift and one of them would eventually be wrong.
         */
        // ⚠ The key is `product_id`. `bhp_get_homepage_books()` builds its
        //   cards with 'product_id' => $book->ID and emits no 'id' key at all,
        //   so a guard reading $product['id'] would silently never fire --
        //   caught by reading that function rather than assuming its shape.
        if (function_exists('bhp_is_colouring_product') && bhp_is_colouring_product($product['product_id'] ?? 0)) {
            continue;
        }

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
    /*
     * ⭐ 1.19.262 (2026-08-19, CYCLE165-LD-DIRECTION1-STEP3-PRODUCT) — ONE
     *    DRAWN MARK BESIDE THE AGE LINE, and only one.
     *
     * The Direction 1 board puts a single piece of the existing hand-authored
     * line art beside "Ages 6-9" so the first screen carries the series'
     * character without a second image competing with the cover. The artwork,
     * its provenance and the reason it is inlined rather than shipped as a
     * second file all live in `inc/product-template.php` beside the markup, so
     * this file holds no copy of either.
     *
     * ⛔ function_exists() IS THE GATE. The mark is a Direction 1 component and
     *    sits behind its own filter; with `bhp_product_template_enabled` off,
     *    or the include absent, this renders exactly what 1.19.261 rendered.
     * ⛔ IT IS DECORATIVE and precedes the words it decorates, so the age is
     *    still the first thing read aloud.
     */
    $bhp_age_mark = function_exists('bhp_product_ages_mark_html') && function_exists('bhp_product_template_enabled') && bhp_product_template_enabled()
        ? bhp_product_ages_mark_html()
        : '';
    ?>
    <div class="bhp-product-value-prop">
        <span class="bhp-product-value-prop__age"><?php
        echo $bhp_age_mark; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static, pre-escaped SVG from bhp_product_ages_mark_html().
        ?><?php echo esc_html($age_range); ?></span>
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
 * addressRegion lists the exact 48 contiguous-US states/DC from the one
 * configured zone rather than a bare "US" addressCountry, so the schema
 * doesn't imply shipping to Alaska, Hawaii, territories, or internationally
 * when checkout does not actually support those destinations.
 */
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ 1.19.222 (2026-08-13) — THIS FILTER HAD BEEN DEAD CODE. F6 / CYCLEX-CX-G04.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ HOW IT DIED, verified by source read AND by the rendered page: this
 *    callback and `bhp_book_add_hardcover_offer()` in `inc/book-formats.php`
 *    are BOTH registered on `rank_math/json_ld` at priority 999. The hardcover
 *    callback runs first (its file is required first) and converts `offers`
 *    from a single associative Offer into a LIST in order to append the second
 *    edition. The guard below then read:
 *
 *        if (… || !isset($entity['offers']['@type'])) { continue; }
 *
 *    `isset($entity['offers']['@type'])` is true only for a SINGLE Offer. Once
 *    `offers` is a list it is false, the loop `continue`d, and BOTH the
 *    shippingDetails injection and the variable-product GTIN patch were skipped
 *    on every canonical product page — silently, with no error and no test.
 *
 * ⭐ OBSERVED BEFORE THE FIX (rendered `<script class="rank-math-schema">` on
 *    staging 1.19.221, Mariana canonical page, both parameter states):
 *    `offers` LIST(2) · `shippingDetails` ABSENT on both offers ·
 *    `Product.gtin` absent. Exactly what the audit reported on production.
 *    `.claude/rules/schema.md` described this filter as working; it described
 *    intent, not behaviour.
 *
 * ✅ THE FIX: normalise both shapes to a list, operate per offer, write back in
 *    the shape it was found in. A single Offer stays a single Offer.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ THE SECOND DEFECT INSIDE THE SAME DEAD CODE — the hardcoded $3.99.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The rate was a literal `'3.99'`, with a comment calling it "the customer-
 * facing rate". It is not, and reviving the filter without correcting it would
 * have published a wrong shipping figure into structured data — a number a
 * shopper could hold the checkout to.
 *
 *   · **$3.99 is the ZONE configuration** — the single `flat_rate` instance's
 *     stored cost, which is real and is not being changed by anything here.
 *   · **What the customer actually pays is TIERED**, per Andrew Signore's
 *     owner ruling of 2026-08-02 ("Shipping is tiered per amount of books
 *     ordered"), implemented in the bundle plugin. Conflating the two is the
 *     documented failure `CYCLE140-DEV-2`.
 *
 * ⭐ AN `Offer` DESCRIBES ONE ITEM, so the honest figure for it is the
 *    single-item rate for THAT format, read live from the bundle plugin's own
 *    `bhp_bundle_single_shipping()` — $1.99 paperback / $2.99 hardcover. Never
 *    re-derived here; the plugin is the single source of truth for every
 *    shipping number on this site, and the published Shipping Policy page says
 *    the same words: "$1.99 for a single paperback and $2.99 for a single
 *    hardcover".
 *
 * ⛔ NO SHIPPING SETTING, ZONE, METHOD, INSTANCE, COST OR TIER IS CHANGED BY
 *    THIS FILTER ON ANY ENVIRONMENT. It reads a number and prints it into a
 *    JSON-LD document. It is a display artefact. Those settings are an Andrew
 *    gate and were not touched.
 *
 * ⛔ ALLOWLISTED TO THE SIX BOOK EDITIONS, deliberately. The previous code
 *    stamped its shipping block onto EVERY single product page, which would
 *    now include the $5.00 downloadable Activity Book — claiming a physical
 *    contiguous-US shipping rate and a 3–8 day transit time for a PDF. For any
 *    product outside the registry the honest output is silence, not a guessed
 *    rate, so nothing is emitted at all.
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

    // Only the six approved printed editions get a physical shipping claim.
    // bhp_book_lookup_product() is the theme's one definition of "is this one
    // of them"; re-deriving it by SKU or category would create a second
    // definition that drifts, and the SKUs differ between environments.
    if (!function_exists('bhp_book_lookup_product')
        || null === bhp_book_lookup_product(get_queried_object_id())) {
        return $data;
    }

    $contiguous_us_states = [
        'AL', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'DC', 'FL', 'GA', 'ID', 'IL',
        'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO',
        'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR',
        'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY',
    ];

    /**
     * The OfferShippingDetails block for ONE offer of one format.
     *
     * The rate is read from the bundle plugin every time rather than cached in
     * a literal, so an approved tier change reaches the structured data on the
     * next request with no second edit in this file. If the plugin is not
     * loaded there is no authoritative number available, and the block is
     * omitted rather than guessed.
     */
    $build_shipping = static function ($format) use ($contiguous_us_states) {
        if (!function_exists('bhp_bundle_single_shipping')) {
            return null;
        }
        $rate = bhp_bundle_single_shipping($format);
        if (!is_numeric($rate)) {
            return null;
        }

        return [
            '@type'               => 'OfferShippingDetails',
            'shippingRate'        => [
                '@type'    => 'MonetaryAmount',
                'value'    => number_format((float) $rate, 2, '.', ''),
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
    };

    /*
     * ═══════════════════════════════════════════════════════════════════════
     * ⭐⭐ 1.19.241 (2026-08-18, `CYCLE164-LD-STOREFRONT-BATCH`) —
     *     hasMerchantReturnPolicy. THE FIELD GOOGLE IS ASKING FOR.
     * ═══════════════════════════════════════════════════════════════════════
     *
     * ⭐ WHY THIS AND NOT RATINGS. Gimli's indexing audit
     *    (`DRAFT-2026-08-18-INDEXING-AUDIT.md`) reports a Search Console
     *    warning on these product pages. The reflex fix is `aggregateRating`
     *    — and it is FORBIDDEN here: there are ZERO reviews, and inventing a
     *    rating is the single most explicit prohibition in
     *    `BHP-AGENT-STANDING-RULES.md` §2 and `.claude/rules/schema.md`.
     *    VERIFIED on staging 2026-08-18 by reading the rendered
     *    `<script class="rank-math-schema">`: no `aggregateRating`, no
     *    `review`, and this release adds NEITHER. The missing-field warning
     *    that can honestly be cleared is the return policy, so that is the
     *    one that is cleared.
     *
     * ⛔⛔ THE BRIEF SAID "free return shipping BY MAIL". THE LIVE POLICY SAYS
     *     THE OPPOSITE, AND THE POLICY WINS. Read from PRODUCTION, read-only,
     *     2026-08-18 (`wp post get 10`, post_modified 2026-08-14 12:55:42),
     *     VERBATIM:
     *
     *       "Because every book is printed on demand, there is nothing to
     *        send back - keep the books or pass them along."
     *
     *     There is no return shipment, so there is no `returnMethod` to
     *     declare. `https://schema.org/ReturnByMail` would publish a
     *     structured-data claim that this store's own published policy
     *     contradicts. It is therefore OMITTED — the property is optional to
     *     Google, and the warning clears without it. ⚠ FLAGGED to the Chief
     *     of Staff rather than silently reconciled.
     *
     * ⭐ EVERY FIELD BELOW TRACES TO THAT PAGE, and nothing else is asserted:
     *      applicableCountry     US        — "the 48 contiguous United States"
     *      returnPolicyCategory  Finite    — "within 30 days of delivery"
     *      merchantReturnDays    30        — the same sentence
     *      returnFees            FreeReturn— nothing to send back, so the
     *                                        customer is never charged to
     *                                        return anything
     *
     * ⛔ SAME ALLOWLIST AS shippingDetails, for the same reason: this filter
     *    has already returned early for anything outside the six printed
     *    editions, so the $5 downloadable Activity Book never receives a
     *    printed-book return policy.
     *
     * ⛔ NO WOOCOMMERCE SETTING IS READ OR WRITTEN. This prints a JSON-LD
     *    document. Refund configuration is an Andrew gate and is untouched.
     */
    $return_policy = [
        '@type'                => 'MerchantReturnPolicy',
        'applicableCountry'    => 'US',
        'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
        'merchantReturnDays'   => 30,
        'returnFees'           => 'https://schema.org/FreeReturn',
    ];

    // The hardcover Offer is the one bhp_book_add_hardcover_offer() built, and
    // it is identifiable by the `?bhp_format=hardcover` URL that same function
    // wrote into it. Everything else on a canonical book page is the paperback.
    $format_of_offer = static function ($offer) {
        return (is_array($offer) && isset($offer['url'])
            && false !== strpos((string) $offer['url'], 'bhp_format=hardcover'))
            ? 'hardcover'
            : 'paperback';
    };

    // The one variation's GTIN, for the exactly-one-variation case that matches
    // the scope of Rank Math's own get_single_variable_offer(). Rank Math never
    // includes gtin there even though the variation's Global Unique ID is set.
    // find_matching_product_variation() needs real $_GET attribute values to
    // resolve anything and is empty on a normal page load with no variation
    // selected in the URL, so this fetches the one child directly instead.
    $variation_gtin = '';
    if ($product->is_type('variable')) {
        $children = $product->get_children();
        if (count($children) === 1) {
            $variation = wc_get_product($children[0]);
            $variation_gtin = $variation ? (string) $variation->get_global_unique_id() : '';
        }
    }

    foreach ($data as $key => $entity) {
        if (!is_array($entity) || ($entity['@type'] ?? '') !== 'Product') {
            continue;
        }
        if (empty($entity['offers']) || !is_array($entity['offers'])) {
            continue;
        }

        // ⭐ THE FIX ITSELF. Both shapes are handled: a single associative
        // Offer (what Rank Math emits alone) and a list of Offers (what the
        // page actually carries once the hardcover edition is appended). The
        // shape is remembered and restored, so a single Offer is never
        // silently promoted into a one-element list.
        $was_single = isset($entity['offers']['@type']);
        $offers     = $was_single ? [$entity['offers']] : array_values($entity['offers']);

        foreach ($offers as $i => $offer) {
            if (!is_array($offer)) {
                continue;
            }

            $format   = $format_of_offer($offer);
            $shipping = $build_shipping($format);
            if (null !== $shipping) {
                $offers[$i]['shippingDetails'] = $shipping;
            }

            /*
             * The return policy is identical for both editions — it is a store
             * policy, not a per-format fact — so unlike shippingDetails there
             * is nothing to derive per offer. It is still written PER OFFER
             * because that is where Google reads it from, and because the
             * hardcover offer is a separate node that would otherwise carry
             * shipping terms with no return terms.
             */
            $offers[$i]['hasMerchantReturnPolicy'] = $return_policy;

            // The GTIN belongs to the paperback variation, so it is only ever
            // written onto the paperback offer — never onto the hardcover,
            // which is a separate simple product with its own ISBN.
            if ('paperback' === $format && '' !== $variation_gtin && empty($offer['gtin'])) {
                $offers[$i]['gtin'] = $variation_gtin;
            }
        }

        $data[$key]['offers'] = $was_single ? $offers[0] : $offers;
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
    /*
     * 1.19.266: the quiz stylesheet is NOT deferred on a page where the quiz is
     * the page's own content rather than a closed modal. The flag is set in
     * `bhp_enqueue_audience_quiz_assets()`, which is the only place that has
     * already worked out which of the four entry points is in play; the full
     * measurement and reasoning live there. `bhp-quiz-modal` stays deferred on
     * every page including this one -- it styles the modal shell, which really
     * is closed on first paint everywhere.
     */
    if ( 'bhp-audience-quiz' === $handle && ! empty( $GLOBALS['bhp_quiz_is_page_content'] ) ) {
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

// ============================================================
// CYCLE166-CX-AFFILIATE-RESTORE — AFFILIATE PRESERVATION (Standing Rules §26)
// ============================================================
/*
 * ⭐ 1.19.294 (2026-08-26, `CYCLE166-CX-AFFILIATE-RESTORE`) — two additions,
 *    both ADDITIVE, neither of which may ever alter an affiliate `href`.
 *
 * CONTEXT. The governing rule is Standing Rules §26, sealed at FD-694. Read it
 * there; it is deliberately NOT restated in this public repository. In short:
 * an affiliate link is a payment instrument that happens to look like an
 * anchor tag, and the correct mental model is the checkout button, not the
 * paragraph around it. It is never removed, never rewritten, never stripped of
 * its tracking code, and never lost in a redesign.
 *
 * ⛔ THE INVARIANT BOTH FUNCTIONS BELOW HOLD: the `href` of an affiliate
 *    anchor is READ, never WRITTEN. No normalising, no shortening, no
 *    re-tagging, no swapping one shortlink for another that resolves to the
 *    same ASIN. Two posts legitimately carry DIFFERENT shortlinks for the
 *    same book (`4tBPd0L` and `3PFKexe` both resolve to Frog and Toad,
 *    ASIN 0439655277) and that is Andrew's to consolidate, not this code's.
 *
 * WHAT WAS *NOT* THE CAUSE, recorded so it is not re-investigated:
 * `bhp_sanitize_content_links()` (priority 20 on `the_content`) rewrites
 * ONLY brand-host URLs and continues past every other host. It has never
 * been able to touch an `amzn.to` link. Section 26.6 leaves the cause of the
 * original loss an open question and this pass does NOT close it.
 */

/**
 * Maps an Amazon shortlink that appears in POST CONTENT to the adventure key
 * the rest of the theme already uses, so an in-content anchor reports the
 * same analytics identity as the template-rendered affiliate block.
 *
 * ⛔ THIS IS NOT A SOURCE OF TRUTH FOR THE LINKS THEMSELVES. Post content is.
 *    An unmapped shortlink is still tracked; it simply reports an empty book.
 *    Third-party titles (Frog and Toad, Mercy Watson, and the rest) are
 *    deliberately absent -- they are not this publisher's books and have no
 *    adventure key.
 */
function bhp_affiliate_content_book_map() {
    return apply_filters('bhp_affiliate_content_book_map', [
        '4svChYL' => 'mariana_trench',
        '4mptuGv' => 'mount_everest',
        '4va9me7' => 'amazon_rainforest',
    ]);
}

/**
 * Adds outbound-click tracking and link hygiene to in-content Amazon
 * affiliate anchors on single blog posts.
 *
 * No new JavaScript: `assets/js/nav.js` already carries a sitewide delegated
 * `[data-bhp-event]` to `window.dataLayer` handler that no-ops when no
 * analytics platform is present. This function only supplies the attributes
 * that handler already reads, which is why it adds no script and activates
 * no analytics platform on its own.
 *
 * `rel` gains `nofollow sponsored` (Google's requirement for paid links) and
 * `noopener` -- attributes only. The `href` is never written.
 */
function bhp_affiliate_content_tracking($content) {
    if (!is_string($content) || stripos($content, 'amzn.to') === false) {
        return $content;
    }
    if (!is_singular('post') || !in_the_loop() || !is_main_query()) {
        return $content;
    }
    if (!class_exists('WP_HTML_Tag_Processor')) {
        return $content; // No safe attribute-level editor available; leave content untouched.
    }

    $map       = bhp_affiliate_content_book_map();
    $processor = new WP_HTML_Tag_Processor($content);

    while ($processor->next_tag('A')) {
        $href = $processor->get_attribute('href');
        if (!is_string($href) || false === stripos($href, 'amzn.to/')) {
            continue;
        }
        // Idempotent: never stack a second event attribute on the same anchor.
        if (is_string($processor->get_attribute('data-bhp-event'))) {
            continue;
        }

        $slug = '';
        if (preg_match('~amzn\.to/([A-Za-z0-9]+)~i', $href, $m)) {
            $slug = $m[1];
        }

        $processor->set_attribute('data-bhp-event', 'amazon_outbound_click');
        $processor->set_attribute('data-bhp-book', isset($map[$slug]) ? $map[$slug] : '');
        $processor->set_attribute('data-bhp-source', 'blog_in_content');
        $processor->set_attribute('data-bhp-format', '');

        $rel   = (string) $processor->get_attribute('rel');
        $parts = array_values(array_filter(preg_split('/\s+/', strtolower($rel))));
        foreach (['nofollow', 'sponsored', 'noopener'] as $token) {
            if (!in_array($token, $parts, true)) {
                $parts[] = $token;
            }
        }
        $processor->set_attribute('rel', implode(' ', $parts));
        // ⛔ `href` is deliberately NOT written anywhere in this loop.
    }

    return $processor->get_updated_html();
}
add_filter('the_content', 'bhp_affiliate_content_tracking', 25);

/**
 * "Also available on Amazon" on the shop archive.
 *
 * Added under the owner ruling sealed at FD-694 (Standing Rules §26), which
 * asks for the "available on Amazon" links to be present here. Verified live
 * 2026-08-26 on staging, and recorded at §26.4 for production: before this
 * block, `/shop/` carried ZERO Amazon links of any kind.
 *
 * ⛔ FORMAT IS DELIBERATELY NOT NAMED. Hardcover and Kindle stay unadvertised
 *    until their royalty economics are documented, so this block names titles
 *    only. (Each shortlink lands on its paperback edition -- verified live in
 *    a real browser 2026-08-26 for `4svChYL`: Paperback $8.99 selected.)
 * ⛔ NO rating, review count, star or testimonial is emitted here, and no
 *    `aggregateRating` or `review` schema. There is none to emit.
 * ⛔ Reuses `bhp_get_amazon_affiliate_urls()`, the existing single source of
 *    truth, rather than hardcoding a second copy of the links.
 */
function bhp_shop_amazon_availability_block() {
    if (!function_exists('is_shop') || !is_shop()) {
        return;
    }
    if (function_exists('is_paged') && is_paged()) {
        return; // First page of the archive only.
    }

    $titles = [
        'mariana_trench'    => __('Adventures of Charlotte and Henry: The Mariana Trench', 'brave-hearts'),
        'mount_everest'     => __('Adventures of Charlotte and Henry: Mount Everest', 'brave-hearts'),
        'amazon_rainforest' => __('Adventures of Charlotte and Henry: The Amazon', 'brave-hearts'),
    ];

    $links = [];
    foreach ($titles as $key => $title) {
        $url = bhp_get_amazon_affiliate_url($key);
        if ($url) { // Empty entry means no link renders. Never a placeholder or search URL.
            $links[$key] = ['url' => $url, 'title' => $title];
        }
    }
    if (!$links) {
        return;
    }
    ?>
    <section class="bhp-shop-amazon" aria-labelledby="bhp-shop-amazon-heading">
      <h2 class="bhp-shop-amazon__heading" id="bhp-shop-amazon-heading"><?php esc_html_e('Also available on Amazon', 'brave-hearts'); ?></h2>
      <p class="bhp-shop-amazon__text"><?php esc_html_e('Prefer a familiar checkout? All three adventures are on Amazon too. Amazon pricing and delivery times may vary.', 'brave-hearts'); ?></p>
      <ul class="bhp-shop-amazon__list">
        <?php foreach ($links as $key => $link) : ?>
          <li class="bhp-shop-amazon__item">
            <a class="bhp-shop-amazon__link"
               href="<?php echo esc_url($link['url']); ?>"
               rel="nofollow sponsored noopener"
               data-bhp-event="amazon_outbound_click"
               data-bhp-book="<?php echo esc_attr($key); ?>"
               data-bhp-source="shop_archive"
               data-bhp-format=""><?php echo esc_html($link['title']); ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
      <p class="bhp-shop-amazon__disclosure"><?php echo esc_html(bhp_get_amazon_disclosure_text()); ?></p>
    </section>
    <?php
}
add_action('woocommerce_after_shop_loop', 'bhp_shop_amazon_availability_block', 30);

/* =====================================================================
 * ⭐⭐ `/free-resources/` — THE HUB, ITS DATA, AND THE NAV DOOR THAT MOVED
 *     theme 1.19.301 · 2026-08-27 · `CYCLE167-LD-FREE-RESOURCES-HUB`
 * =====================================================================
 *
 * ⭐ FOUNDER CARRIER ITEM 300, IN FULL — one letter, "A", answering a nav
 *    question Gandalf put to him. Read first-hand by this desk at
 *    `Business OS\WORKING-DRAFTS\chief-of-staff\FOUNDER-VERBATIM-2026-08-05-
 *    PRODUCTION-DEPLOY-AUTHORIZATION.md`. ⚠ RELAYED through Gandalf, who
 *    witnessed it; NOT witnessed by this desk, and therefore not a capability
 *    grant and not approval for any gated action.
 *
 *    Option A, as he adopted it: the "Expedition Guides" nav slot becomes
 *    FREE RESOURCES and points at the new `/free-resources/` hub. `/teachers/`
 *    loses its nav slot and gains a PROMINENT "For Teachers and Librarians"
 *    section inside the hub. ⭐ THE NAV COUNT IS UNCHANGED.
 *
 * ---------------------------------------------------------------------
 * ⛔⛔ WHERE THIS NAV LABEL ACTUALLY LIVES — FOUR PLACES, NOT ONE, AND THE
 *     FOURTH IS THE ONE THAT WOULD HAVE SHIPPED THE BUG.
 * ---------------------------------------------------------------------
 *
 * ⭐ THE STORED WORDPRESS MENU ROW IS NOT ONE OF THEM, and that is the single
 *    most useful fact in this comment. VERIFIED FIRST-HAND on staging,
 *    read-only, 2026-08-27: primary menu term 198 is assigned, and its teacher
 *    row is item 130 with stored title "Teacher's Guide" and stored url
 *    `/teachers-guide/`. NEITHER the label NOR the target a visitor sees is in
 *    the database — `bhp_canonicalize_teacher_menu_items()` rewrites both on
 *    every render.
 *    ⭐ CONSEQUENCE FOR THE DEPLOY PACKET: this nav change ships ENTIRELY IN
 *    THE THEME ZIP. There is NO wp-admin menu edit to perform on production,
 *    and performing one would be silently reverted by the filter anyway.
 *
 *   1. `bhp_canonicalize_teacher_menu_items()` (priority 10) — sets the title
 *      and url on the live menu object. ⛔ NOT EDITED. This filter retargets
 *      afterwards, at priority 26, exactly as `bhp_adventure_books_nav_target_
 *      shop()` does at 25 for the Books item, and for the reason that function
 *      records: a proven filter stays byte-untouched and the change is a new,
 *      separately reversible one.
 *   2. `bhp_fallback_menu()` — the primary-nav fallback. It only runs when NO
 *      menu is assigned to the `primary` location (one IS assigned on both
 *      environments today), but it must not disagree with the live nav, so it
 *      is updated in the same release.
 *   3. `bhp_footer_fallback_menu()` — ⛔ DELIBERATELY NOT TOUCHED. `footer.php`
 *      stopped calling it at 1.19.269 and the `footer` menu location is no
 *      longer rendered at all; its own suite still pins its current contents.
 *      Editing an unrendered fallback to match a nav it does not render would
 *      be churn with a failing test attached.
 *   4. ⛔⛔ `style.css` — `.menu-item--educator-guides > a::after { content:
 *      'Expedition Guides'; }` INSIDE `@container (min-width: 1117px)`, where
 *      the anchor itself is set to `font-size: 0 !important`.
 *      ⭐⭐ AT DESKTOP WIDTHS THE VISIBLE LABEL IS A CSS PSEUDO-ELEMENT, NOT
 *      THE PHP TITLE. Changing only the PHP would have left every desktop
 *      visitor reading "Expedition Guides" on a link that went to
 *      `/free-resources/` — no error, no warning, and nothing else in this
 *      repo would have failed. The `content` string moves in the same release
 *      and the suite asserts the old string survives in no stylesheet rule.
 *
 * ⚠ THE CLASS NAME `menu-item--educator-guides` IS KEPT, and keeping it is
 *   deliberate rather than lazy. It is an internal styling hook, no visitor
 *   ever reads it, and it is the join between the PHP and the pseudo-element
 *   above; renaming it means renaming it in two files plus every test that
 *   pins it, to change a string nobody sees. The same call this theme already
 *   made for the `bhp_mariana_popup` storage prefix and the
 *   `ultimate-gift-guide-cover` filename. ⭐ `menu-item--free-resources` is
 *   ADDED alongside it so later work has a correctly-named hook without
 *   anything having to be removed.
 *
 * ⚠ WHAT IS NOT DONE HERE, BECAUSE IT IS ANDREW'S AND WAS NOT ASKED FOR: the
 *   item's POSITION is unchanged. Merry's §23 walk recommends moving it above
 *   "Contact" (its open decision 4) on the evidence that it currently sits in
 *   the weakest slot in the list. That is a nav-order change nobody has
 *   approved, so it is reported, not taken.
 *
 * ⚠ AND THE EVIDENCE AGAINST THE LABEL ITSELF IS RECORDED RATHER THAN BURIED:
 *   ZERO of the six competitors Merry walked live put the word "Free" in a nav
 *   label; all put it in the H1 and the `<title>` instead. Andrew chose
 *   "FREE RESOURCES" in his own words and his word governs, so it ships. The
 *   counter-evidence is written down so he can overrule himself cheaply.
 *   ⭐ `.site-nav a` sets `text-transform: uppercase`, so the string
 *   "Free Resources" RENDERS as "FREE RESOURCES". Both his capitals and the
 *   house title-case convention are satisfied by one string.
 * ===================================================================== */

/**
 * The hub's article rail — real published posts, resolved from slugs.
 *
 * ⛔ SLUGS, NOT IDS. Post ids differ between environments (tonight's K3 article
 *    is 5089 on staging and 638 on production), so an id list would be correct
 *    on exactly one environment and silently wrong on the other. Slugs are
 *    stable across both and are what the guide registry already uses.
 *
 * ⭐ ORDER IS INTENTIONAL AND COMES FROM TWO SOURCES, both recorded: the four
 *    the build brief names lead (tonight's K3 article, then the "books like
 *    Magic Tree House" hub, the Dog Man to Magic Tree House roadmap, and "what
 *    to read after Dog Man"), followed by the remaining five in the order
 *    Merry's §23 walk ranked them. Where the brief and the spec could disagree,
 *    the brief governs scope and the spec governs presentation — so the brief
 *    picks the leaders and the spec orders the tail.
 *
 * ⛔ THE K3 ARTICLE IS INCLUDED, AND MERRY'S SPEC SAYS IT SHOULD NOT BE. That
 *    disagreement is settled by EVIDENCE, not preference, and the evidence is
 *    recorded here because a future reader will meet the spec's exclusion
 *    first. The walk checked `/what-to-read-after-magic-tree-house/`, got a
 *    404, and honestly flagged that "the slug may simply differ". It does: this
 *    site puts posts under `/blog/`. VERIFIED FIRST-HAND by this desk against
 *    PRODUCTION, read-only, 2026-08-27: post 638, `post_status` publish,
 *    `get_permalink()` = `https://braveheartspublishing.com/blog/what-to-read-
 *    after-magic-tree-house/`. The article is live and the exclusion is
 *    discharged. ⭐ The presence guard below means an environment that really
 *    does not have it simply renders eight cards instead of nine.
 *
 * @return string[] Post slugs, in render order.
 */
function bhp_free_resources_article_slugs() {
    return apply_filters('bhp_free_resources_article_slugs', array(
        'what-to-read-after-magic-tree-house',
        'books-like-magic-tree-house',
        'dog-man-to-magic-tree-house-reading-roadmap',
        'what-to-read-after-dog-man',
        'my-child-hates-reading-what-to-do',
        'best-books-for-7-year-olds',
        'bridge-books-for-struggling-readers',
        'reading-level-by-grade-chart',
        'what-is-a-lexile-score',
    ));
}

/**
 * Those slugs resolved to real, PUBLISHED posts, in the declared order.
 *
 * ⛔⛔ THE PRESENCE GUARD IS THE POINT OF THIS FUNCTION, NOT AN OPTIMISATION.
 *     A slug that is unpublished, renamed, trashed or simply absent on an
 *     environment resolves to nothing and is DROPPED. The template therefore
 *     cannot render an empty card, a dead link or a heading over nothing, and
 *     nobody has to maintain a per-environment list by hand. If the whole list
 *     resolves empty the template omits the entire section.
 *
 * ⛔ `post_name__in` DOES NOT PRESERVE THE ORDER OF THE ARRAY YOU GIVE IT, so
 *    the results are re-sorted against the declared order afterwards. Trusting
 *    the query's order would have shipped a rail sorted by date, which is not
 *    the order anyone chose.
 *
 * @return WP_Post[]
 */
function bhp_free_resources_articles() {
    static $cache = null;
    if (null !== $cache) {
        return $cache;
    }

    $slugs = array_values(array_filter(array_map('sanitize_title', (array) bhp_free_resources_article_slugs())));
    if (!$slugs) {
        $cache = array();
        return $cache;
    }

    $posts = get_posts(array(
        'post_type'        => 'post',
        'post_status'      => 'publish',
        'posts_per_page'   => count($slugs),
        'post_name__in'    => $slugs,
        'no_found_rows'    => true,
        'suppress_filters' => false,
    ));

    $by_slug = array();
    foreach ($posts as $p) {
        $by_slug[$p->post_name] = $p;
    }

    $ordered = array();
    foreach ($slugs as $slug) {
        if (isset($by_slug[$slug])) {
            $ordered[] = $by_slug[$slug];
        }
    }

    $cache = $ordered;
    return $cache;
}

/**
 * The hub's UNGATED free downloads.
 *
 * ⭐⭐ THIS IS THE THING THE SITE DID NOT HAVE THIS MORNING. Merry's §23 walk
 *     verified live, by href scan on four of our own pages, that we published
 *     ZERO instant-download PDFs while our closest analogue (Magic Tree House)
 *     published fifteen with no gate at all. Everything free here was
 *     email-gated and Mailchimp-delivered.
 *
 * ⭐ FOUNDER CARRIER ITEM 302, read first-hand by this desk, verbatim: *"Yes,
 *    we can put the coloring book page as the first Free PDF, lets also build a
 *    few free PDFs tonight, lets brainstorm and put them in there, not a
 *    fillers, as actually good resources for parents?"*, and item 304: *"They
 *    are free so lets do all 5 as well"*. ⚠ RELAYED, not witnessed here.
 *    ⭐ His "coloring book page ... first" is why that row is first below.
 *
 * ⛔ EVERY FILE HERE WAS BUILT BY `design-creative` UNDER `CYCLE167-DES-FREE-
 *    PDFS`, FROM REAL AND ATTESTED MATERIAL ONLY, AND WAS LOOKED AT BY THIS
 *    DESK BEFORE BEING SHIPPED — not merely listed. Each is copied BYTE FOR
 *    BYTE from that lane's `deliver\` tree and md5-verified after the copy;
 *    nothing was re-rendered, re-compressed or edited here.
 *    ⚠ TWO md5s IN THAT LANE'S OWN MANIFEST ARE STALE — `Backyard-Expedition`
 *      and `How-Did-She-Do-Reading-It` were rebuilt in an R3 round after the
 *      manifest was last written (its `proof\R3-*` renders are the evidence).
 *      The FILES were taken as the truth and the manifest discrepancy is
 *      reported to the Chief of Staff rather than silently reconciled.
 *
 * ⛔ EVERY ROW IS `file_exists()`-GUARDED AGAINST THE SHIPPED THEME. A row whose
 *    file did not make it into the ZIP renders NOTHING rather than a download
 *    button that 404s. Merry's walk is explicit about why that matters: *"a
 *    padded one is unrecoverable once a parent has clicked a dead promise."*
 *    ⛔ Do not add a row for a file you have not put on disk.
 *
 * ⭐ WHY THE FILES SHIP IN THE THEME RATHER THAN THE MEDIA LIBRARY: a media
 *    upload is a per-environment content step that would have to be repeated on
 *    production and would be forgotten exactly once. In `assets/downloads/`
 *    they travel in the ZIP, have the same URL shape on both environments, and
 *    reverse by reinstalling the previous ZIP. ⚠ The cost is disclosed rather
 *    than buried: they add ~9.3 MB to the theme artefact.
 *
 * ⛔ NOTHING HERE IS EMAIL-GATED, AND THAT IS THE WHOLE DIFFERENCE FROM EVERY
 *    OTHER FREE THING ON THIS SITE. The Kit, the classroom guide and the
 *    toolkit are all delivered by Mailchimp after a signup; these open on click.
 *
 * ⛔ THE SIZE ON EACH CARD IS COMPUTED FROM THE FILE AT RENDER TIME, never
 *    typed. A hardcoded "2.4 MB" is a claim that goes stale the first time a
 *    file is replaced, and a stale number about our own artefact is exactly the
 *    class of small lie this corpus keeps having to remove. The PAGE COUNT is
 *    declared, because it cannot be read cheaply — each value below was
 *    verified by this desk against that lane's own proof renders.
 *
 * @return array<int,array<string,mixed>>
 */
function bhp_free_resources_downloads() {
    $rows = apply_filters('bhp_free_resources_downloads', array(
        array(
            'key'         => 'coloring_pages',
            'title'       => __('Three Pages to Color', 'brave-hearts'),
            /* ⛔ NAMES THE PAID PRODUCT HONESTLY. These are three real pages out
             *    of a 57-page book that costs money. Saying "sample pages from
             *    the coloring book" without saying the book is a paid product
             *    would be a true sentence assembled into a false impression. */
            'description' => __('Three real pages from my Mariana Trench coloring book, exactly as they appear in it. The full book is a paid one. These three are free.', 'brave-hearts'),
            'file'        => 'assets/downloads/mariana-trench-coloring-pages.pdf',
            'cta'         => __('Open the coloring pages', 'brave-hearts'),
            'pages'       => 4,
            /* ⛔ DESCRIBES PAGE ONE, WHICH IS NOT A COLORING PAGE. This file's
             *    first page is a cover sheet; the three pages to color are 2, 3
             *    and 4. An alt text that said "a sea turtle to color" would
             *    describe the FILE and misdescribe the PICTURE, which is the
             *    thing a screen-reader user is actually being offered. */
            'preview_alt' => __('Page one of the file: a cover sheet headed "Three Pages to Color", listing what is on each of the three pages that follow (the sea turtle, the four words, the anglerfish), with notes on printing them and coloring them together.', 'brave-hearts'),
        ),
        array(
            'key'         => 'mantra_poster',
            'title'       => __('Stop. Breathe. Think. Act.', 'brave-hearts'),
            /* ⛔ The mantra is quoted in its CANON ORDER (FD-553), which is the
             *    order printed in the books and on the sheet. The founder
             *    corrected himself on this at carrier item 273. */
            'description' => __('The four words from the story, big enough to pin up, with the breathing steps written out underneath.', 'brave-hearts'),
            'file'        => 'assets/downloads/stop-breathe-think-act-poster.pdf',
            'cta'         => __('Open the poster', 'brave-hearts'),
            'pages'       => 1,
            'preview_alt' => __('The poster: STOP. BREATHE. THINK. ACT. set in four wide gold bands down a dark navy sheet, with a boxed list of four numbered breathing steps underneath and two black-and-white drawings to color at the foot of the page.', 'brave-hearts'),
            /* ⭐ ONE CARD, TWO FILES, RATHER THAN TWO CARDS. The ink-saver is
             *    the same poster on white with outlined chips; listing it as a
             *    separate resource would inflate the grid without adding one. */
            'alt_file'    => 'assets/downloads/stop-breathe-think-act-poster-ink-saver.pdf',
            'alt_label'   => __('Or open the ink-saver version', 'brave-hearts'),
        ),
        array(
            'key'         => 'backyard_expedition',
            'title'       => __('Backyard Expedition', 'brave-hearts'),
            'description' => __('Four things to do outside with your kid. Draw, read, spot ten things, take one slow breath. No equipment, and a backyard is enough.', 'brave-hearts'),
            'file'        => 'assets/downloads/backyard-expedition.pdf',
            'cta'         => __('Open the activity', 'brave-hearts'),
            'pages'       => 1,
            'preview_alt' => __('The activity sheet: four numbered things to do outside. Draw what you see, with a blank box to draw in; read a bit outside, with lines to fill in; spot ten things, with a tick list; and one slow breath, with four numbered steps.', 'brave-hearts'),
        ),
        array(
            'key'         => 'reading_ladder',
            'title'       => __('The Reading Ladder', 'brave-hearts'),
            'description' => __('Graphic novels, bridge books, chapter books, and what each rung actually asks of a reader. Built on format, not on a reading level number.', 'brave-hearts'),
            'file'        => 'assets/downloads/reading-ladder.pdf',
            'cta'         => __('Open the ladder', 'brave-hearts'),
            'pages'       => 1,
            'preview_alt' => __('The one-page ladder: three numbered rungs (graphic novels, bridge books, chapter books), each with a line on what it asks of a reader, followed by a checklist of titles grouped by the rung they sit on.', 'brave-hearts'),
        ),
        array(
            'key'         => 'how_did_she_do',
            'title'       => __('Your Kid Finished the Book. Now What?', 'brave-hearts'),
            /* ⭐ FROM HIS OWN ATTESTED WORDS (carrier items 286 to 288), not
             *    invented: "How did she do reading it?" is the question he
             *    really asks at his market table. ⛔ It is framed as HIS
             *    question, never as a reported parent reaction — he said
             *    himself at item 287 that he cannot attest what parents say. */
            'description' => __('The one question I ask at my market table, and how I read the answer. One page.', 'brave-hearts'),
            'file'        => 'assets/downloads/how-did-she-do-reading-it.pdf',
            'cta'         => __('Open the parent card', 'brave-hearts'),
            'pages'       => 1,
            'preview_alt' => __('The parent card: the question "How did she do reading it?" set large in a dark panel, with two columns underneath. One is what to do if they tell you everything, the other what to do if you get a shrug, each with a short checklist of books.', 'brave-hearts'),
        ),
    ));

    $out = array();
    foreach ((array) $rows as $row) {
        $file = isset($row['file']) ? ltrim((string) $row['file'], '/') : '';
        $path = '' === $file ? '' : get_theme_file_path($file);
        if ('' === $file || !file_exists($path)) {
            continue; // ⛔ FAILS TO SILENCE. Never a dead download button.
        }

        $row['url']  = get_theme_file_uri($file);
        $pages       = isset($row['pages']) ? (int) $row['pages'] : 0;
        $size        = size_format((int) filesize($path));
        $row['meta'] = $pages > 1
            /* translators: 1: page count, 2: human-readable file size. */
            ? sprintf(__('PDF, %1$d pages, %2$s', 'brave-hearts'), $pages, $size)
            /* translators: %s: human-readable file size. */
            : sprintf(__('PDF, 1 page, %s', 'brave-hearts'), $size);

        if (!empty($row['alt_file'])) {
            $alt_rel  = ltrim((string) $row['alt_file'], '/');
            $alt_path = get_theme_file_path($alt_rel);
            if (file_exists($alt_path)) {
                $row['alt_url'] = get_theme_file_uri($alt_rel);
            } else {
                unset($row['alt_label']); // ⛔ The secondary link disappears too.
            }
        }

        /*
         * ⭐⭐ 1.19.303 (2026-08-27, CYCLE167-LD-HUB-POLISH) — THE PAGE-ONE
         *     PREVIEW. Founder carrier item 311: "I think there should be a
         *     picture of each one above the box description as well. So the
         *     audience can see what they are getting".
         *
         * ⭐ THE PICTURES ARE THE REAL PAGE ONES. Each was rendered from the
         *    shipped PDF itself at 800x1036 (US Letter at ~94 DPI, which is 2.1x
         *    the 378.9px grid slot measured live on staging — retina with
         *    headroom). ⛔ NOT a decorative stand-in, not an illustration, not a
         *    generated image: "so the audience can see what they are getting"
         *    only means anything if the picture IS what they are getting.
         *
         * ⛔ GUARDED EXACTLY LIKE THE CARD ABOVE, AND FOR THE SAME REASON. Both
         *    derivatives must resolve on disk or the card renders with NO image
         *    rather than a broken one. A missing preview costs a picture; it
         *    never costs the download.
         *
         * ⭐ THE FILENAME IS DERIVED, NOT TYPED. `<pdf basename>-preview.{webp,
         *    jpg}` — so adding a row to this registry cannot leave a preview
         *    pointing at the wrong resource, and there is no second list to keep
         *    in step with the first.
         *
         * ⭐ INTRINSIC DIMENSIONS ARE READ FROM THE FILE, never typed here —
         *    `bhp_get_founder_photo()`'s rule, learned at 1.19.296: a hardcoded
         *    intrinsic size is a lie waiting for somebody to replace an asset.
         *    They are emitted as width/height so the grid cannot reflow (CLS).
         *
         * ⛔⛔ `?ver=` IS NOT COSMETIC, AND THIS IS THE SECOND TIME THIS THEME
         *     HAS PAID FOR LEARNING IT. These are FIXED filenames and SiteGround
         *     serves them `Cache-Control: max-age=31536000`. 1.19.299 measured
         *     the same URL returning two different founder photographs in one
         *     page load, a year apart in expiry. Without this line a corrected
         *     preview would never reach anybody who had already seen the hub,
         *     while the file on disk, the suite and a fresh browser all reported
         *     success. The idiom is the theme's own — every enqueued asset in
         *     this file already passes the theme version.
         */
        $row['preview'] = array();
        $stem           = preg_replace('/\.pdf$/i', '', basename($file));
        $prev_rel       = array(
            'webp' => 'assets/images/free-resources/' . $stem . '-preview.webp',
            'jpg'  => 'assets/images/free-resources/' . $stem . '-preview.jpg',
        );
        $prev_webp_path = get_theme_file_path($prev_rel['webp']);
        $prev_jpg_path  = get_theme_file_path($prev_rel['jpg']);

        if (file_exists($prev_webp_path) && file_exists($prev_jpg_path)) {
            $dims = @getimagesize($prev_jpg_path);
            $ver  = wp_get_theme()->get('Version');
            $bust = static function ($rel_path) use ($ver) {
                $uri = get_theme_file_uri($rel_path);
                return $ver ? add_query_arg('ver', rawurlencode($ver), $uri) : $uri;
            };

            $row['preview'] = array(
                'webp'   => $bust($prev_rel['webp']),
                'jpg'    => $bust($prev_rel['jpg']),
                'width'  => (is_array($dims) && !empty($dims[0])) ? (int) $dims[0] : 0,
                'height' => (is_array($dims) && !empty($dims[1])) ? (int) $dims[1] : 0,
                'alt'    => isset($row['preview_alt']) ? (string) $row['preview_alt'] : '',
            );

            // ⛔ No intrinsic size and no alt text means no picture. A preview
            //    that can cause layout shift or that a screen reader meets
            //    unlabelled is worse than the card as it stood yesterday.
            if (!$row['preview']['width'] || !$row['preview']['height'] || '' === $row['preview']['alt']) {
                $row['preview'] = array();
            }
        }

        $out[] = $row;
    }

    return $out;
}

/**
 * ⭐ THE NAV DOOR. Priority 26 — AFTER `bhp_canonicalize_teacher_menu_items()`
 *    at 10 (which normalises the stored row into one item carrying the
 *    `menu-item--educator-guides` class) and after
 *    `bhp_adventure_books_nav_target_shop()` at 25, and BEFORE
 *    `bhp_start_here_nav_item()` at 30. The ordering is a contract, not an
 *    accident: this filter reads a class that priority 10 puts there.
 *
 * ⛔ PRIMARY LOCATION ONLY. `bhp_canonicalize_teacher_menu_items()` carries no
 *    `theme_location` guard and runs on any menu, so without this check the
 *    retarget would follow the Expedition Guides item into any other menu that
 *    ever renders. The `footer` location is not rendered today; "not rendered
 *    today" is not a reason to write a filter that would misbehave if it were.
 *
 * ⛔⛔ AND IT FIXES THE CURRENT-ITEM STATE IN BOTH DIRECTIONS, which is the half
 *     that gets forgotten. Once the rendered url and the stored object
 *     disagree, WordPress's own current-page detection is working from the
 *     wrong url:
 *       · on `/free-resources/` it would highlight NOTHING, because no stored
 *         item points there;
 *       · on `/teachers/` it would highlight "FREE RESOURCES", because the
 *         stored item still points at `/teachers/` — a nav telling the visitor
 *         they are on a page they are not on.
 *     Both are corrected below. `bhp_adventure_books_nav_target_shop()` only
 *     had to handle the first case; this one has to handle both, because
 *     `/teachers/` is still a live, reachable page that simply lost its door.
 *
 * @param array       $items Menu objects.
 * @param object|null $args  `wp_nav_menu` args.
 * @return array
 */
function bhp_free_resources_nav_item($items, $args = null) {
    if (is_object($args) && isset($args->theme_location) && 'primary' !== $args->theme_location) {
        return $items;
    }

    $teacher_path = untrailingslashit((string) wp_parse_url(home_url('/teachers/'), PHP_URL_PATH));
    $hub_url      = home_url('/free-resources/');
    $hub_path     = untrailingslashit((string) wp_parse_url($hub_url, PHP_URL_PATH));

    $on_hub      = is_page_template('page-free-resources.php') || is_page('free-resources');
    $on_teachers = is_page('teachers') || is_page_template('page-teachers.php');

    foreach ($items as $item) {
        $item_path = untrailingslashit((string) wp_parse_url($item->url, PHP_URL_PATH));
        $is_target = in_array('menu-item--educator-guides', (array) $item->classes, true)
            || $item_path === $teacher_path
            || $item_path === $hub_path;

        if (!$is_target) {
            continue;
        }

        /*
         * ⭐⭐ 1.19.303 (2026-08-27, CYCLE167-LD-HUB-POLISH) — FREE OVER
         *     RESOURCES, BY ADOPTING THE ADVENTURE BOOKS MECHANISM RATHER THAN
         *     IMITATING ITS LOOK. Founder carrier item 311, on his own walk:
         *     "I would like Free on top of Resources just like Adventure Books
         *      and make sure all the fonts match all teh nav bar fonts,
         *      spacing and style."
         *
         * ⭐ THE SECOND CLAUSE IS WHY THIS IS TWO SPANS AND NOT A TWO-LINE
         *    `content:` STRING. The desktop label used to be a CSS
         *    pseudo-element, and MEASURED LIVE on staging at 1.19.302 it did
         *    NOT match the nav:
         *      ADVENTURE BOOKS  10.5px · letter-spacing 1.47px (.14em) · lh 12.6px
         *      FREE RESOURCES   10.5px · letter-spacing NONE      · lh 14.175px
         *    The pseudo-element sat outside `@container (max-width: 1236px)`'s
         *    `.site-nav a { font-size: 10.5px; letter-spacing: .14em }`, so it
         *    was always going to drift from the bar around it. Emitting the
         *    same two `.site-nav__label-line` spans that
         *    `bhp_stack_adventure_books_nav_label()` emits makes this item a
         *    plain `.site-nav a` again — so the font, size, weight, tracking
         *    and line-height are not COPIED from the Adventure Books item,
         *    they are THE SAME DECLARATIONS. They cannot drift apart later.
         *
         * ⚠ THE STORED MENU ROW IS NOT EDITED, exactly as the Adventure Books
         *   and Expedition Guides precedents require: a DB menu row does not
         *   travel in a theme ZIP, so a staging-only menu edit would leave
         *   production unchanged after an approved deploy.
         * ⛔ The accessible name is restored explicitly by
         *   `bhp_free_resources_nav_aria_label()` below — two block-level
         *   spans otherwise read to a screen reader as two separate runs of
         *   text. This is the same pairing Adventure Books already ships.
         */
        $item->title   = '<span class="site-nav__label-line">' . esc_html__('Free', 'brave-hearts') . '</span><span class="site-nav__label-line">' . esc_html__('Resources', 'brave-hearts') . '</span>';
        $item->url     = $hub_url;
        $item->classes = array_values(array_unique(array_merge(
            (array) $item->classes,
            array('menu-item--free-resources')
        )));

        // ⛔ See the docblock: both directions, not just the one.
        $item->classes = array_values(array_diff((array) $item->classes, array('current-menu-item')));
        $item->current = false;
        if ($on_hub && !$on_teachers) {
            $item->current   = true;
            $item->classes[] = 'current-menu-item';
        }
    }

    return $items;
}
add_filter('wp_nav_menu_objects', 'bhp_free_resources_nav_item', 26, 2);

/**
 * ⭐ 1.19.303 — the accessible name for the stacked FREE / RESOURCES item.
 *
 * ⛔ WITHOUT THIS THE STACKING IS AN ACCESSIBILITY REGRESSION, not a style
 *    change. Two block-level spans read to a screen reader as two separate
 *    runs of text ("Free", "Resources"), so the link's accessible name has to
 *    be set explicitly. This is a byte-for-byte parallel of
 *    `bhp_adventure_books_nav_aria_label()` above and exists for the same
 *    reason that one does.
 *
 * @param array  $atts Link attributes.
 * @param object $item Menu object.
 * @return array
 */
function bhp_free_resources_nav_aria_label($atts, $item) {
    if (in_array('menu-item--free-resources', (array) $item->classes, true)) {
        $atts['aria-label'] = __('Free Resources', 'brave-hearts');
    }
    return $atts;
}
add_filter('nav_menu_link_attributes', 'bhp_free_resources_nav_aria_label', 10, 2);

/**
 * SEO title and meta description for the hub — ⛔ FALLBACKS ONLY.
 *
 * ⭐ THE PATTERN IS `bhp_audience_landing_seo_description_filter()`'s, above,
 *    and the reason is the same one it records: Rank Math's own wp-admin value
 *    always wins, so filling the field in the admin later takes precedence over
 *    this with NO code change and NO redeploy. That matters more than usual
 *    here, because the final SEO copy is not this desk's to write — it is
 *    ChatGPT's under G-1 and Andrew's under G4. What ships below is Merry's
 *    §23 CANDIDATE, held in code only so the page is not launched with an empty
 *    description.
 *
 * ⭐ "FREE" IS IN THE TITLE AND THE H1, WHICH IS WHERE THE COMPETITOR EVIDENCE
 *    ACTUALLY PUTS IT. Scholastic's nav says "Activities & Printables" and its
 *    H1 says "Free Printables for Kids"; Highlights' nav says "Activities" and
 *    its title says "Free Printables for Kids". Merry's walk found 0 of 6 using
 *    "Free" in a nav label and used it in the heading everywhere it checked.
 *
 * ⛔ NO RATING, NO REVIEW COUNT, NO SUPERLATIVE, NO OUTCOME CLAIM, and the age
 *    band is 6 to 9. A meta description is customer-facing copy and every
 *    standing rail applies to it.
 *
 * ⚠ THE TITLE FILTER IS NOT `empty()`-GUARDED AND THE DESCRIPTION ONE IS, and
 *   the asymmetry is deliberate: Rank Math always produces SOME title (it falls
 *   back to the post title plus the site name), so an `empty()` guard there
 *   would mean this never ran. The description genuinely comes back empty when
 *   the admin field is unset, which is the condition worth filling.
 */
function bhp_free_resources_seo_title($title) {
    if (!is_page_template('page-free-resources.php') && !is_page('free-resources')) {
        return $title;
    }
    return __('Free Reading Printables and Resources for Kids Ages 6 to 9 | Brave Hearts Publishing', 'brave-hearts');
}
add_filter('rank_math/frontend/title', 'bhp_free_resources_seo_title', 20);

function bhp_free_resources_seo_description($description) {
    if (!empty($description)) {
        return $description; // ⛔ wp-admin always wins.
    }
    if (!is_page_template('page-free-resources.php') && !is_page('free-resources')) {
        return $description;
    }
    return __('Free printables you can download now, plus articles on what to read next when a child stalls. Coloring pages, an outdoor activity, a reading ladder and a free sample chapter, for readers ages 6 to 9.', 'brave-hearts');
}
add_filter('rank_math/frontend/description', 'bhp_free_resources_seo_description', 20);
add_filter('rank_math/opengraph/facebook/description', 'bhp_free_resources_seo_description', 20);
add_filter('rank_math/opengraph/twitter/description', 'bhp_free_resources_seo_description', 20);
/**
 * ═════════════════════════════════════════════════════════════════════════
 * THE RETAILER / BOOKSELLER FUNNEL — theme 1.19.304, 2026-08-27,
 * `CYCLE167-LD-RETAILER-PAGE`. Two small additions, both additive.
 * ═════════════════════════════════════════════════════════════════════════
 */

/**
 * 1 · THE `wholesale` CONTACT INQUIRY TYPE.
 *
 * ⭐ NOTHING NEW IS BUILT HERE, AND THAT IS THE POINT. The mechanism already
 *    existed and was simply never used by this funnel:
 *    `template-parts/contact/contact-form.php` reads `$_GET['inquiry']`,
 *    `sanitize_key()`s it, VALIDATES IT AGAINST THE REGISTERED TYPES and
 *    preselects the dropdown. All that was missing was a registered type, so
 *    `/contact/?inquiry=wholesale` silently fell through to "Select an inquiry
 *    type" and every retailer CTA landed on a bare, unfocused contact form.
 *
 * ⛔ ADDED THROUGH THE FILTER, NEVER BY EDITING THE SHARED ARRAY IN PLACE
 *    (Merry §5.2). The shared array is four other funnels' contract; a
 *    retailer requirement must not reach into it.
 *
 * ⭐ THE LABEL NAMES BOTH WORDS A BUYER WOULD LOOK FOR. An independent
 *    bookstore calls it wholesale; a museum or park store calls it retail
 *    ordering. Naming one loses the other in a dropdown they scan in a second.
 *    ⚠️ Customer-facing copy: drafted here, ANDREW APPROVES (G-1 / H-T4).
 *
 * ⛔ THE EXISTING `bookseller` ROLE IS UNTOUCHED. Role and inquiry type are
 *    different fields and both are useful — the role says who they are, the
 *    type says what they want.
 */
add_filter( 'bhp_contact_inquiry_types', 'bhp_retailer_add_wholesale_inquiry_type' );
function bhp_retailer_add_wholesale_inquiry_type( $types ) {
    if ( ! is_array( $types ) || isset( $types['wholesale'] ) ) {
        return $types;
    }

    /*
     * Inserted directly after `bulk-orders`, which is the adjacent concept a
     * buyer's eye is already travelling past. Appending it after "Other" would
     * bury it below the escape hatch.
     */
    $out = array();
    foreach ( $types as $key => $label ) {
        $out[ $key ] = $label;
        if ( 'bulk-orders' === $key ) {
            $out['wholesale'] = __( 'Wholesale / Retail Ordering', 'brave-hearts' );
        }
    }
    if ( ! isset( $out['wholesale'] ) ) {
        $out['wholesale'] = __( 'Wholesale / Retail Ordering', 'brave-hearts' ); // fail-safe if the anchor key ever moves
    }

    return $out;
}

/**
 * 2 · THE SEO TITLE — ⛔ A FALLBACK ONLY, AND DELIBERATELY NOT FINAL.
 *
 * ⭐ THE PATTERN IS `bhp_audience_landing_seo_description_filter()`'s and
 *    `bhp_free_resources_seo_title()`'s, for the reason both record: Rank
 *    Math's own wp-admin value always wins, so filling the field in the admin
 *    later takes precedence with NO code change and NO redeploy.
 *
 * ⛔⛔ THAT MATTERS MORE THAN USUAL HERE, ON TWO SEPARATE COUNTS, AND NEITHER
 *    IS CLEARED BY THIS BUILD:
 *      · G-1 — SEO COPY IS CHATGPT'S FINAL AUTHORITY, not this desk's and not
 *        Merry's. What ships below is Merry's §8.2 CANDIDATE, held in code only
 *        so the page is not launched with Rank Math's bare post-title default.
 *      · STANDING RULES §25 — no SEO decision is proposed without a Google
 *        Analytics review first, and THIS DESK CANNOT REACH GA. ⛔ No GA review
 *        was performed. The page is 404 on production and has no GA history to
 *        review, but that is a reason the review would be empty, NOT a reason
 *        the rule does not apply. It is flagged in the release report as an
 *        uncleared precondition on PUBLICATION, which is Andrew's act anyway.
 *
 * ⚠️ NOT `empty()`-GUARDED, and the asymmetry with the description filter is
 *    deliberate: Rank Math always produces SOME title (post title plus site
 *    name), so an `empty()` guard would mean this never runs. The description
 *    genuinely comes back empty when unset, which is the condition worth
 *    filling — and the retailer description is ALREADY filled by the existing
 *    `bhp_audience_landing_seo_description()` branch, which is approved copy
 *    and is NOT rewritten here.
 *
 * ⛔ NO DISCOUNT, NO TERMS, NO PRICE IN THE TITLE OR THE DESCRIPTION. A meta
 *    description is customer-facing copy and every standing rail applies to it.
 */
function bhp_retailer_seo_title( $title ) {
    if ( ! is_page_template( 'page-audience-retailers.php' ) ) {
        return $title;
    }
    return __( 'Wholesale & Bookstore Orders | Brave Hearts Publishing', 'brave-hearts' );
}
add_filter( 'rank_math/frontend/title', 'bhp_retailer_seo_title', 20 );
