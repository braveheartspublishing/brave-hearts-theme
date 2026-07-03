/**
 * Sitewide Mariana Trench teacher-guide popup: trigger timing, scroll
 * depth, frequency capping, and a lightweight focus trap. Server-side
 * eligibility (page type, admin, cart/checkout, etc.) is already decided
 * in PHP — if #mariana-popup isn't in the page, this file does nothing
 * except the cross-page success check described below.
 *
 * No email addresses or personal data are ever stored client-side.
 */
(function () {
    'use strict';

    var STORAGE_DISMISSED_UNTIL = 'bhp_mariana_popup_dismissed_until';
    var STORAGE_SIGNED_UP = 'bhp_mariana_popup_signed_up';
    var SESSION_SHOWN = 'bhp_mariana_popup_shown_session';
    var SESSION_PENDING_SUBMIT = 'bhp_mariana_popup_pending_submit';
    var DISMISS_DAYS = 10;

    function pushEvent(eventName, extra) {
        if (typeof window.dataLayer === 'undefined' || !Array.isArray(window.dataLayer)) {
            return;
        }
        var page = window.location.pathname || '';
        window.dataLayer.push(Object.assign({
            event: eventName,
            source: 'mariana_popup',
            page_path: page
        }, extra || {}));
    }

    function readLocal(key) {
        try {
            return window.localStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }

    function writeLocal(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch (e) {
            /* localStorage unavailable (private mode, etc.) — fail silently. */
        }
    }

    function readSession(key) {
        try {
            return window.sessionStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }

    function writeSession(key, value) {
        try {
            if (value === null) {
                window.sessionStorage.removeItem(key);
            } else {
                window.sessionStorage.setItem(key, value);
            }
        } catch (e) {
            /* sessionStorage unavailable — fail silently. */
        }
    }

    // Cross-page success detection: the popup form is a normal POST that
    // navigates away. On success the server redirects straight to the
    // Mariana thank-you page via the existing whitelisted redirect key. We
    // set a one-shot session flag right before that navigation and check it
    // here on every subsequent page load, so popup_success only fires when
    // the visitor actually lands on the thank-you page next.
    (function checkPendingSuccess() {
        var pending = readSession(SESSION_PENDING_SUBMIT);
        if (!pending) {
            return;
        }
        writeSession(SESSION_PENDING_SUBMIT, null);
        if (window.location.pathname.indexOf('mariana-guide-thank-you') !== -1) {
            pushEvent('popup_success');
            writeLocal(STORAGE_SIGNED_UP, '1');
        }
    })();

    var popup = document.querySelector('[data-bhp-popup]');
    if (!popup) {
        return;
    }

    // Permanent suppression after a real signup, in this browser only.
    if (readLocal(STORAGE_SIGNED_UP) === '1') {
        return;
    }

    var forceOpen = popup.getAttribute('data-force-open') === '1';
    var dismissedUntil = parseInt(readLocal(STORAGE_DISMISSED_UNTIL), 10) || 0;
    var withinCooldown = Date.now() < dismissedUntil;

    if (!forceOpen && withinCooldown) {
        return;
    }
    if (!forceOpen && readSession(SESSION_SHOWN) === '1') {
        return;
    }

    var overlay = popup.querySelector('[data-bhp-popup-overlay]');
    var dialog = popup.querySelector('.mariana-popup__dialog');
    var closeButton = popup.querySelector('[data-bhp-popup-close]');
    var dismissLink = popup.querySelector('[data-bhp-popup-dismiss]');
    var form = popup.querySelector('form');
    var lastFocused = null;

    function getFocusable() {
        var nodes = dialog.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        return Array.prototype.filter.call(nodes, function (el) {
            return el.offsetParent !== null;
        });
    }

    function trapFocus(event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            close(true);
            return;
        }
        if (event.key !== 'Tab') {
            return;
        }
        var focusable = getFocusable();
        if (!focusable.length) {
            return;
        }
        var first = focusable[0];
        var last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    function show() {
        lastFocused = document.activeElement;
        popup.hidden = false;
        popup.classList.add('is-open');
        document.addEventListener('keydown', trapFocus, true);

        var focusable = getFocusable();
        (focusable[0] || dialog).focus();

        writeSession(SESSION_SHOWN, '1');
        pushEvent('popup_view', { page_type: popup.getAttribute('data-page-type') || '' });
    }

    function close(wasDismissed) {
        popup.hidden = true;
        popup.classList.remove('is-open');
        document.removeEventListener('keydown', trapFocus, true);

        if (wasDismissed) {
            var until = Date.now() + DISMISS_DAYS * 24 * 60 * 60 * 1000;
            writeLocal(STORAGE_DISMISSED_UNTIL, String(until));
            pushEvent('popup_close', { page_type: popup.getAttribute('data-page-type') || '' });
        }

        if (lastFocused && typeof lastFocused.focus === 'function') {
            lastFocused.focus();
        }
    }

    if (closeButton) {
        closeButton.addEventListener('click', function () {
            close(true);
        });
    }
    if (dismissLink) {
        dismissLink.addEventListener('click', function (event) {
            event.preventDefault();
            close(true);
        });
    }
    if (overlay) {
        overlay.addEventListener('click', function () {
            close(true);
        });
    }
    if (form) {
        form.addEventListener('submit', function () {
            // Fires synchronously before the browser navigates away.
            writeSession(SESSION_PENDING_SUBMIT, '1');
            pushEvent('popup_submit', { page_type: popup.getAttribute('data-page-type') || '' });
        });
    }

    if (forceOpen) {
        show();
        return;
    }

    // Gated trigger: the scroll condition alone can't open the popup before
    // a minimum engagement time has passed, and a separate fallback timer
    // opens it regardless if the visitor never scrolls far enough.
    //   Desktop: (>=8s elapsed AND scroll >= 40%) OR 15s elapsed
    //   Mobile:  (>=10s elapsed AND scroll >= 50%) OR 18s elapsed
    var isMobile = window.matchMedia('(max-width: 767px)').matches;
    var minTimeMs = isMobile ? 10000 : 8000;
    var fallbackMs = isMobile ? 18000 : 15000;
    var scrollPct = isMobile ? 50 : 40;
    var triggered = false;
    var minTimeElapsed = false;
    var minTimeTimerId = null;
    var fallbackTimerId = null;

    function getScrollPercent() {
        var doc = document.documentElement;
        var scrolled = window.scrollY || doc.scrollTop || 0;
        // Guards pages too short to scroll: height floors at 1 instead of
        // going to 0 or negative, so the percentage stays finite.
        var height = Math.max(doc.scrollHeight - doc.clientHeight, 1);
        return ((scrolled + doc.clientHeight) / (height + doc.clientHeight)) * 100;
    }

    function cleanupTriggers() {
        if (minTimeTimerId) {
            clearTimeout(minTimeTimerId);
            minTimeTimerId = null;
        }
        if (fallbackTimerId) {
            clearTimeout(fallbackTimerId);
            fallbackTimerId = null;
        }
        window.removeEventListener('scroll', onScroll);
    }

    function trigger() {
        if (triggered) {
            return;
        }
        triggered = true;
        cleanupTriggers();
        show();
    }

    function onScroll() {
        if (!minTimeElapsed) {
            return;
        }
        if (getScrollPercent() >= scrollPct) {
            trigger();
        }
    }

    minTimeTimerId = window.setTimeout(function () {
        minTimeElapsed = true;
        // The visitor may already be past the scroll threshold and idle
        // (no further scroll event to react to) — check right away.
        onScroll();
    }, minTimeMs);

    fallbackTimerId = window.setTimeout(trigger, fallbackMs);
    window.addEventListener('scroll', onScroll, { passive: true });
})();
