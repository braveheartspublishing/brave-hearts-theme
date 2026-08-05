/**
 * COMPLETE COLLECTION BAND — the format toggle on the homepage and /books/.
 * ============================================================================
 *
 * theme 1.19.177, 2026-08-05, CYCLE144-LD-51.
 *
 * Andrew Signore, 2026-08-05, current-turn order (RELAYED through the Chief of
 * Staff and witnessed by the main session; NOT witnessed first-hand by the
 * agent that wrote this file): the homepage "Get the Complete Collection" CTA,
 * and its /books/ twin, must add the collection to the cart and land on the
 * checkout page rather than link to /complete-collection/.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * THE ONE JOB, AND WHY IT IS A SEPARATE FILE
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * This file rewrites ONE hidden input's value when the visitor picks a format,
 * using the `data-bhp-collection-action` contract that already ships in
 * `audience-landing.js` and `parent-landing.js`. That is the whole behaviour.
 *
 * It is its own file rather than three lines in `nav.js` because `nav.js` loads
 * on every page of the site including checkout, and because a band-only defect
 * should be de-enqueueable without touching sitewide navigation. It is not a
 * fork of the landing-page toggles: those own per-format PANELS and per-format
 * PRICES that this band deliberately does not have, so sharing their function
 * would mean importing behaviour with nothing to act on.
 *
 * ⛔ WHAT THIS FILE DOES NOT DO. It adds nothing to a cart, calls no endpoint,
 *    reads no price, computes no discount, shipping figure, tax or total, and
 *    submits no form. The add-and-checkout itself is entirely the bundle
 *    plugin's: `bundle-drawer.js` intercepts `form.bhp-bundle-form`, adds over
 *    the Store API and navigates to /checkout/, and with JS off the plugin's
 *    `template_redirect` handler does the same server-side. Nothing here
 *    touches either path.
 *
 * ⚠ DEGRADED, NOT BROKEN, WITH JS OFF: the buttons do nothing and the CTA buys
 *   the server-rendered default format (hardcover). The customer still reaches
 *   a correct three-book checkout. Stated rather than glossed.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * THE DEFECT THIS SHAPE PREVENTS
 * ─────────────────────────────────────────────────────────────────────────────
 * Scoping is per band, not per document. Both the homepage band and the /books/
 * band render the same markup, and `#complete-collection`-style pages may grow
 * a second one. A document-wide `querySelectorAll` would let a click on one
 * band rewrite another band's hidden field — the same class of defect that once
 * let the Collection page's sticky bar and its pricing panel disagree about
 * format. Every lookup below starts from the clicked button's own band.
 */
(function () {
    'use strict';

    function qsa(sel, ctx) {
        return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
    }

    function pushEvent(eventName, extra) {
        if (typeof window.dataLayer === 'undefined' || !Array.isArray(window.dataLayer)) {
            return;
        }
        window.dataLayer.push(Object.assign({
            event: eventName,
            page_path: window.location.pathname || ''
        }, extra || {}));
    }

    /**
     * The band a control belongs to.
     *
     * `.home-collection-feature` is the band's own wrapper and is present on
     * both surfaces; the `<section>` is the last-resort fallback so a future
     * markup tweak degrades to "still scoped to a section" rather than to
     * "scoped to the whole document".
     */
    function bandOf(el) {
        return el.closest('.home-collection-feature') || el.closest('section') || document;
    }

    function setFormat(band, format) {
        qsa('[data-bhp-band-format-btn]', band).forEach(function (btn) {
            var isMatch = btn.getAttribute('data-bhp-band-format-btn') === format;
            btn.classList.toggle('is-selected', isMatch);
            btn.setAttribute('aria-checked', isMatch ? 'true' : 'false');
            /*
             * Roving tabindex. A radiogroup is ONE tab stop; arrow keys move
             * within it. Without this a keyboard user tabs through both
             * buttons and never learns they are alternatives.
             */
            btn.setAttribute('tabindex', isMatch ? '0' : '-1');
        });

        /*
         * THE ONLY MUTATION THAT MATTERS. The hidden field the plugin reads.
         * `complete_<fmt>_smart` is the plugin's own de-duplicating action —
         * it adds only the titles this cart is missing, which is what makes a
         * double-tap harmless.
         */
        qsa('[data-bhp-collection-action]', band).forEach(function (input) {
            input.value = 'complete_' + format + '_smart';
        });
    }

    function initToggle(group) {
        var buttons = qsa('[data-bhp-band-format-btn]', group);
        if (buttons.length < 2) {
            return;
        }
        var band = bandOf(group);

        buttons.forEach(function (btn, index) {
            btn.addEventListener('click', function () {
                var format = btn.getAttribute('data-bhp-band-format-btn');
                setFormat(band, format);
                pushEvent('collection_band_format_selected', { format: format });
            });

            btn.addEventListener('keydown', function (e) {
                if (['ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp'].indexOf(e.key) === -1) {
                    return;
                }
                e.preventDefault();
                var dir = (e.key === 'ArrowRight' || e.key === 'ArrowDown') ? 1 : -1;
                var next = buttons[(index + dir + buttons.length) % buttons.length];
                next.focus();
                next.click();
            });
        });
    }

    function init() {
        qsa('[data-bhp-collection-band]').forEach(initToggle);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
