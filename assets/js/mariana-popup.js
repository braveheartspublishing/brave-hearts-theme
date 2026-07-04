/**
 * Generic sitewide lead popup engine: trigger timing, scroll depth,
 * frequency capping, and a lightweight focus trap. Server-side eligibility
 * (page type, admin, cart/checkout, etc.) is already decided in PHP — each
 * popup template supplies its own config via a single data-popup-config
 * JSON attribute, so this file has no popup-specific content or behavior
 * hardcoded into it. Multiple independent popups (teacher, parent, future
 * ones) share this one engine while keeping fully isolated storage and
 * analytics namespaces.
 *
 * No email addresses or personal data are ever stored client-side.
 */
(function () {
    'use strict';

    var DISMISS_DAYS = 10;

    // Defaults exactly match the original single-purpose Mariana teacher
    // popup, so a popup element with no data-popup-config at all behaves
    // identically to before this file was generalized.
    var DEFAULT_CONFIG = {
        eventPrefix: '',
        source: 'mariana_popup',
        storagePrefix: 'bhp_mariana_popup',
        thankYouPath: 'mariana-guide-thank-you',
        trigger: {
            mode: 'gated',
            desktop: { minDelay: 8000, fallbackDelay: 15000, scrollPct: 40 },
            mobile: { minDelay: 10000, fallbackDelay: 18000, scrollPct: 50 }
        }
    };

    function parseConfig(el) {
        var raw = el.getAttribute('data-popup-config');
        if (!raw) {
            return DEFAULT_CONFIG;
        }
        try {
            var parsed = JSON.parse(raw);
            return {
                eventPrefix: parsed.eventPrefix || DEFAULT_CONFIG.eventPrefix,
                source: parsed.source || DEFAULT_CONFIG.source,
                storagePrefix: parsed.storagePrefix || DEFAULT_CONFIG.storagePrefix,
                thankYouPath: parsed.thankYouPath || DEFAULT_CONFIG.thankYouPath,
                trigger: parsed.trigger || DEFAULT_CONFIG.trigger
            };
        } catch (e) {
            return DEFAULT_CONFIG;
        }
    }

    function eventName(prefix, suffix) {
        return prefix ? prefix + '_' + suffix : 'popup_' + suffix;
    }

    function pushEvent(source, eventNameValue, extra) {
        if (typeof window.dataLayer === 'undefined' || !Array.isArray(window.dataLayer)) {
            return;
        }
        var page = window.location.pathname || '';
        window.dataLayer.push(Object.assign({
            event: eventNameValue,
            source: source,
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

    // Cross-page success detection: each popup form is a normal POST that
    // navigates away. On success the server redirects straight to that
    // popup's own thank-you page via the existing whitelisted redirect key.
    // We record which popup is pending (by its own storage/event prefixes,
    // never by name/email) right before that navigation, and check it here
    // on every subsequent page load — a shared key works for any number of
    // independent popups without them needing to know about each other.
    var PENDING_SUBMIT_KEY = 'bhp_popup_pending_submit';

    (function checkPendingSuccess() {
        var raw = readSession(PENDING_SUBMIT_KEY);
        if (!raw) {
            return;
        }
        writeSession(PENDING_SUBMIT_KEY, null);
        var pending;
        try {
            pending = JSON.parse(raw);
        } catch (e) {
            return;
        }
        if (!pending || !pending.thankYouPath || !pending.storagePrefix) {
            return;
        }
        if (window.location.pathname.indexOf(pending.thankYouPath) !== -1) {
            pushEvent(pending.source, eventName(pending.eventPrefix, 'success'), {});
            writeLocal(pending.storagePrefix + '_signed_up', '1');
        }
    })();

    var popup = document.querySelector('[data-bhp-popup]');
    if (!popup) {
        return;
    }

    var config = parseConfig(popup);
    var STORAGE_DISMISSED_UNTIL = config.storagePrefix + '_dismissed_until';
    var STORAGE_SIGNED_UP = config.storagePrefix + '_signed_up';
    var SESSION_SHOWN = config.storagePrefix + '_shown_session';

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
        pushEvent(config.source, eventName(config.eventPrefix, 'view'), { page_type: popup.getAttribute('data-page-type') || '' });
    }

    function close(wasDismissed) {
        popup.hidden = true;
        popup.classList.remove('is-open');
        document.removeEventListener('keydown', trapFocus, true);

        if (wasDismissed) {
            var until = Date.now() + DISMISS_DAYS * 24 * 60 * 60 * 1000;
            writeLocal(STORAGE_DISMISSED_UNTIL, String(until));
            pushEvent(config.source, eventName(config.eventPrefix, 'close'), { page_type: popup.getAttribute('data-page-type') || '' });
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
            writeSession(PENDING_SUBMIT_KEY, JSON.stringify({
                eventPrefix: config.eventPrefix,
                source: config.source,
                storagePrefix: config.storagePrefix,
                thankYouPath: config.thankYouPath
            }));
            pushEvent(config.source, eventName(config.eventPrefix, 'submit'), { page_type: popup.getAttribute('data-page-type') || '' });
        });
    }

    if (forceOpen) {
        show();
        return;
    }

    // Two trigger modes, selected per popup via config.trigger.mode:
    //   'simple' — whichever of (timer OR scroll%) happens first, no gating.
    //   'gated'  — the scroll condition can't fire before a minimum
    //              engagement time, with a separate, longer, ungated
    //              fallback timer for visitors who never scroll that far.
    var isMobile = window.matchMedia('(max-width: 767px)').matches;
    var triggerConfig = config.trigger || DEFAULT_CONFIG.trigger;
    var mode = triggerConfig.mode === 'simple' ? 'simple' : 'gated';
    var deviceConfig = (isMobile ? triggerConfig.mobile : triggerConfig.desktop) || triggerConfig.desktop || {};

    var triggered = false;
    var minTimeElapsed = (mode === 'simple');
    var minTimeTimerId = null;
    var fallbackTimerId = null;
    var scrollPct = deviceConfig.scrollPct;

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
        if (!minTimeElapsed || typeof scrollPct !== 'number') {
            return;
        }
        if (getScrollPercent() >= scrollPct) {
            trigger();
        }
    }

    if (mode === 'simple') {
        // Timer and scroll race unconditionally; whichever fires first wins.
        if (typeof deviceConfig.delay === 'number') {
            minTimeTimerId = window.setTimeout(trigger, deviceConfig.delay);
        }
        if (typeof scrollPct === 'number') {
            window.addEventListener('scroll', onScroll, { passive: true });
        }
    } else {
        // Gated: minimum-time timer flips a flag and immediately re-checks
        // scroll position, since a visitor who scrolled past the threshold
        // before the minimum time elapsed and then stopped would otherwise
        // never generate another scroll event to catch up on. The fallback
        // timer is tracked separately so both can be cleared together once
        // trigger() fires — the popup can still only ever open once.
        minTimeTimerId = window.setTimeout(function () {
            minTimeElapsed = true;
            onScroll();
        }, deviceConfig.minDelay);

        if (typeof deviceConfig.fallbackDelay === 'number') {
            fallbackTimerId = window.setTimeout(trigger, deviceConfig.fallbackDelay);
        }
        window.addEventListener('scroll', onScroll, { passive: true });
    }
})();
