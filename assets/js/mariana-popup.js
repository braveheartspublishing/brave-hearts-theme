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
 *
 * ---------------------------------------------------------------------
 * ⛔⛔ THIS FILE IS SERVED TO THE PUBLIC UNMINIFIED. ITS COMMENTS ARE
 *     PUBLIC OUTPUT — WRITE THEM ACCORDINGLY.
 *
 * Stylesheets in this theme are comment-stripped at build time by
 * `bhp_minified_style_src()` (functions.php) and `tools/build-css.mjs`, so
 * a CSS essay costs the visitor nothing. THERE IS NO EQUIVALENT FOR JS.
 * Every byte of this comment block is downloaded by every visitor and read
 * by every crawler.
 *
 * ⛔ Therefore, in THIS file and every other file under `assets/js/`:
 *     - NO internal call names (the conversational aliases used in briefs);
 *     - NO internal working-draft filenames or Business OS paths;
 *     - NO verbatim founder quotations.
 *   Engineering reasoning stays. Internal machinery does not.
 *
 * ⭐ 1.19.293 (2026-08-26, `CYCLE166-CX-ANNOTATION-STRIP`) removed two
 *    internal call names from the comments below. The wider cleanup — the
 *    remaining quotations and identifiers in this and other served scripts —
 *    is recommended as a JS analogue of the CSS build step, which keeps the
 *    prose in the repository and off the wire. Until that exists, this
 *    constraint is enforced by whoever edits the file.
 * ---------------------------------------------------------------------
 * WAVE 1 (2026-08-04, theme 1.19.168) — THREE ADDITIONS, NO REWRITES.
 *
 * `.claude/rules/funnels.md` is explicit: "don't fork the engine to add a
 * third funnel; extend the config schema instead." All three additions are
 * config-schema extensions, and a popup element that supplies none of them
 * behaves exactly as it did in 1.19.167.
 *
 *   1. MULTI-POPUP INIT. The engine used to read exactly one
 *      `document.querySelector('[data-bhp-popup]')`. It now initialises
 *      every `[data-bhp-popup]` element independently, each with its own
 *      config, storage prefix, event prefix and trigger. That is what makes
 *      an exit-intent modal possible alongside the existing timed popup
 *      WITHOUT a second engine file. Isolation is unchanged and is now
 *      per-element rather than per-page.
 *
 *   2. TRIGGER MODE 'exit'. Desktop leave-intent (pointer exits through the
 *      top of the viewport) plus a mobile fallback (a fast upward flick
 *      after the visitor has already read some depth). Both are held behind
 *      the SAME 20-second dwell floor the quiz modal uses — Andrew Signore,
 *      2026-08-04: "20 seconds please". Nothing automatic may open before
 *      `minDelay` has elapsed, by any path.
 *
 *   3. SHARED SESSION-FREQUENCY GUARD. `config.sessionGuard` is a list of
 *      sessionStorage keys that BLOCK this popup if any of them is already
 *      set. Every popup that opens writes SHARED_SESSION_SHOWN_KEY, and
 *      quiz-modal.js writes its own key, so "one capture modal per session,
 *      whichever got there first" is enforced by data rather than by each
 *      surface remembering the others exist.
 *
 * ⛔ FUNNEL ISOLATION IS UNTOUCHED. No storage prefix, analytics event
 *    prefix, lead-magnet key or thank-you path is renamed, merged or
 *    shared between the parent and teacher funnels. The one genuinely
 *    shared key (SHARED_SESSION_SHOWN_KEY) is a *frequency* flag: it
 *    carries no funnel identity, no audience, no offer and no personal
 *    data, and it is sessionStorage, so it dies with the tab. It cannot
 *    make a teacher signup affect parent suppression state or vice versa —
 *    that state still lives entirely under each funnel's own prefix.
 *
 * ---------------------------------------------------------------------
 * 1.19.173 (2026-08-05) — EXIT-TRIGGER HARDENING. Andrew Signore reported a
 * live false positive: the modal opened while he was CLICKING a purchase CTA
 * on `/complete-collection/`. Four additive guards now sit in front of both
 * exit paths — interaction cooldown, mobile gesture requirement, desktop
 * upward-pointer-velocity, and navigation-pending. Full reasoning, including
 * the reproduced measurement, is in the block above `var exitAttached`.
 *
 * ⛔ NOTHING outside trigger mode 'exit' is touched. Modes 'simple' and
 *    'gated', the dwell floor, the session guard, the drawer/overlay
 *    deferral, the focus trap and every storage/analytics key are byte-for-
 *    byte what they were in 1.19.171.
 * ---------------------------------------------------------------------
 * 1.19.204 (2026-08-06) — `config.abTest`, ONE MORE CONFIG-SCHEMA EXTENSION.
 *
 * `.claude/rules/funnels.md`: "don't fork the engine ... extend the config
 * schema instead." A popup that supplies no `abTest` block behaves exactly
 * as it did in 1.19.203 — every line below is inside an `if (config.abTest)`
 * guard or a helper only that branch calls.
 *
 * WHAT IT DOES, in the order it does it:
 *   1. Assigns a variant ONCE PER VISITOR and remembers it in a FIRST-PARTY
 *      COOKIE (`config.abTest.cookie`), not localStorage and not
 *      sessionStorage. A cookie is the correct store here for two reasons
 *      that are not interchangeable with the funnel's own keys: the
 *      assignment must survive a new tab and a new session (sessionStorage
 *      cannot), and it is a visitor-level bucketing value rather than
 *      funnel suppression state, so it deliberately does NOT live under
 *      `storagePrefix` and cannot move parent or teacher suppression.
 *   2. Assignment happens at INIT, while the popup is still `hidden`, and
 *      the losing variant's markup is REMOVED FROM THE DOM before anything
 *      is ever painted. That is what makes "no flicker" true by
 *      construction rather than by a CSS race.
 *   3. ⭐ CACHE SAFETY. The server renders BOTH variants, byte-identically,
 *      for every visitor. Nothing about the assignment exists in the HTML,
 *      so a full-page cache cannot pin one variant to one cached page — the
 *      same discipline the consent/pixel work uses (`test-consent-mode-
 *      cache-safety.php`).
 *   4. Stamps the assigned variant into the form's hidden
 *      `config.abTest.field` input, so the server's Mailchimp tag map can
 *      read it. The browser sends the SHORT KEY only ('A'/'B'); the tag
 *      strings live server-side in `bhp_get_popup_ab_variants()`.
 *   5. Carries `variant` and `content_name` on the view/submit/success
 *      dataLayer events. `content_name` is what the Meta pixel's Lead event
 *      reads, so each variant is separately attributable. The event PREFIX
 *      is unchanged (`parent_popup`), so funnel isolation and every existing
 *      analytics name are untouched.
 * ---------------------------------------------------------------------
 */
(function () {
    'use strict';

    /*
     * ═══════════════════════════════════════════════════════════════════
     * ⭐ 1.19.292 (2026-08-26, `CYCLE166-CX-CAPTURE-REPAIR`) — THE COOLDOWN
     *    IS SPLIT BY HOW THE VISITOR LEFT. NOT EVERY CLOSE MEANS "NO".
     * ═══════════════════════════════════════════════════════════════════
     *
     * ⭐ THE DISTINCTION IS INTENT, and it is the whole justification:
     *
     *    THE X BUTTON IS A DECISION. A visitor who finds the small close
     *    control and clicks it has read the offer and declined it. Asking
     *    again soon is nagging. → 10 DAYS, UNCHANGED, deliberately.
     *
     *    AN OVERLAY CLICK AND `Escape` ARE REFLEXES, NOT DECISIONS. They
     *    are what people do to make an unexpected thing go away — very
     *    often before reading it at all. On touch especially, an overlay
     *    tap is frequently a MIS-TAP: the dialog does not fill the screen,
     *    a finger lands beside it, and the offer is gone. Treating that as
     *    a considered "no" and then hiding the offer for ten days throws
     *    away a visitor who never actually saw it.
     *
     * ⭐ 24 HOURS IS THE CHOICE, and the brief allowed 24-48h. The reasoning,
     *    recorded so it is not re-litigated from taste:
     *      - It is the shortest of the offered options, and the asymmetry
     *        should favour recovery: the cost of re-asking a genuinely
     *        uninterested visitor is one dismissed overlay, while the cost
     *        of a ten-day silence after a mis-tap is a lost subscriber.
     *      - It still guarantees AT MOST ONE PROMPT PER DAY per surface, so
     *        it cannot become the "popup on every page load" pattern the
     *        session guard and dwell floor already exist to prevent.
     *      - 48h splits the difference without buying anything: nobody has
     *        argued a mis-tap deserves two days of silence rather than one,
     *        and 24h is the value that can be lengthened later on evidence.
     *        Lengthening a cooldown after launch is safe; shortening one
     *        re-exposes visitors who already said no.
     *
     * ⚠️ THIS IS A JUDGEMENT, NOT A MEASUREMENT. There is no experiment
     *    behind 24h and none is claimed. It is reversible in one constant.
     *
     * ⛔ NO STORAGE KEY IS ADDED, RENAMED OR SPLIT. Both paths still write
     *    the SAME `<prefix>_dismissed_until` key, per funnel, exactly as
     *    before — only the VALUE written differs. Funnel isolation is
     *    therefore untouched by construction: the parent funnel writes its
     *    key, the teacher funnel writes its own, and neither can see the
     *    other's. The event name pushed on close is also unchanged, so no
     *    analytics series is split or renamed.
     */
    var DISMISS_DAYS = 10;

    // A reflexive exit — overlay click or Escape. Hours, not days.
    var SOFT_DISMISS_HOURS = 24;

    // Written by EVERY popup that opens, read only by popups that ask for
    // it via config.sessionGuard. See the header note on why this does not
    // breach funnel isolation.
    var SHARED_SESSION_SHOWN_KEY = 'bhp_popup_shown_session';

    // Defaults exactly match the original single-purpose Mariana teacher
    // popup, so a popup element with no data-popup-config at all behaves
    // identically to before this file was generalized.
    var DEFAULT_CONFIG = {
        eventPrefix: '',
        source: 'mariana_popup',
        storagePrefix: 'bhp_mariana_popup',
        thankYouPath: 'mariana-guide-thank-you',
        sessionGuard: [],
        abTest: null,
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
                sessionGuard: Array.isArray(parsed.sessionGuard) ? parsed.sessionGuard : DEFAULT_CONFIG.sessionGuard,
                abTest: parseAbTest(parsed.abTest),
                trigger: parsed.trigger || DEFAULT_CONFIG.trigger
            };
        } catch (e) {
            return DEFAULT_CONFIG;
        }
    }

    /**
     * An abTest block is accepted only when it is complete and internally
     * consistent. A malformed block yields null, which puts the popup back on
     * the 1.19.203 code path rather than half-applying an experiment — a
     * half-applied experiment produces data nobody can read.
     */
    function parseAbTest(raw) {
        if (!raw || typeof raw !== 'object') {
            return null;
        }
        if (typeof raw.cookie !== 'string' || !raw.cookie) {
            return null;
        }
        var keys = raw.variants && typeof raw.variants === 'object' ? Object.keys(raw.variants) : [];
        if (keys.length < 2) {
            return null;
        }
        return {
            cookie: raw.cookie,
            days: typeof raw.days === 'number' && raw.days > 0 ? raw.days : 180,
            field: typeof raw.field === 'string' ? raw.field : '',
            // QA only, and it can only ever arrive from the server, which
            // emits it exclusively on staging. See the template.
            force: typeof raw.force === 'string' ? raw.force : '',
            keys: keys,
            variants: raw.variants
        };
    }

    function readCookie(name) {
        try {
            var parts = String(document.cookie || '').split(';');
            for (var i = 0; i < parts.length; i++) {
                var pair = parts[i];
                var eq = pair.indexOf('=');
                if (eq === -1) {
                    continue;
                }
                if (pair.slice(0, eq).trim() === name) {
                    return decodeURIComponent(pair.slice(eq + 1).trim());
                }
            }
        } catch (e) {
            /* cookies unavailable — treated as unassigned. */
        }
        return null;
    }

    function writeCookie(name, value, days) {
        try {
            document.cookie = name + '=' + encodeURIComponent(value) +
                '; Max-Age=' + Math.round(days * 24 * 60 * 60) +
                '; path=/; SameSite=Lax' +
                (window.location.protocol === 'https:' ? '; Secure' : '');
        } catch (e) {
            /* fail silently — the visitor still sees a variant this page view. */
        }
    }

    /**
     * Sticky per visitor. An existing cookie always wins, and is only
     * discarded when it names a variant this popup does not define (an
     * experiment that has since been re-cut), in which case the visitor is
     * re-bucketed once rather than shown nothing.
     */
    function assignVariant(ab) {
        // A forced variant is for QA and is deliberately NOT persisted — it
        // must not overwrite a real visitor's sticky assignment.
        if (ab.force && ab.keys.indexOf(ab.force) !== -1) {
            return ab.force;
        }
        var stored = readCookie(ab.cookie);
        if (stored && ab.keys.indexOf(stored) !== -1) {
            return stored;
        }
        // 50/50 across however many variants are declared; two, here.
        var picked = ab.keys[Math.floor(Math.random() * ab.keys.length)] || ab.keys[0];
        writeCookie(ab.cookie, picked, ab.days);
        return picked;
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
            // `content_name` rides along ONLY when the popup that submitted
            // was running an experiment. The Meta pixel's Lead bridge reads
            // it; when it is absent the pixel falls back to its own fixed
            // per-funnel name exactly as before.
            var extra = {};
            if (pending.variant) {
                extra.variant = pending.variant;
            }
            if (pending.contentName) {
                extra.content_name = pending.contentName;
            }
            pushEvent(pending.source, eventName(pending.eventPrefix, 'success'), extra);
            writeLocal(pending.storagePrefix + '_signed_up', '1');
        }
    })();

    /**
     * One popup. Everything below used to run at module scope against a
     * single element; it is now a function so several independent popups
     * can coexist on one page without sharing a single variable.
     */
    function initPopup(popup) {
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

        /*
         * ═════════════════════════════════════════════════════════════
         * ⭐ 1.19.271 (2026-08-19, `CYCLE165-LD-ITERATE-7-KIT-CTA-POPUP`)
         *    THE THREE FREQUENCY RULES BELOW NOW SUPPRESS THE **AUTOMATIC**
         *    OPEN ONLY. THEY NO LONGER ABANDON `initPopup()`.
         * ═════════════════════════════════════════════════════════════
         *
         * ⭐ Andrew Signore, verbatim, relayed by the Chief of Staff as
         *    first-hand founder wording: "When someone on the blog hits Get
         *    the free kit - it should be a pop up where they can immediately
         *    subscribe- not send them to the reluctant reader page. Less
         *    steps."
         *
         * ⛔ WHY THE `return`s HAD TO GO, AND WHY THAT IS NOT A LOOSENING.
         *    An explicit click on a control whose label says "Get the Free
         *    Kit" is a REQUEST. Cooldown, once-per-session and the shared
         *    session guard all exist to stop the popup ARRIVING UNASKED;
         *    none of them is a reason to refuse somebody who just asked.
         *    But every listener in this function — close, dismiss, overlay,
         *    focus trap, submit handoff — is wired BELOW this point, so a
         *    bare `return` left no popup to open and no way to close it.
         *    The flag keeps the setup and skips only the arming.
         *
         * ⛔ NOTHING IS WIDENED FOR THE AUTOMATIC PATH. `autoSuppressed` is
         *    consulted at exactly one place: the trigger-arming block at the
         *    end of this function. Every storage key, cooldown length,
         *    session key and guard name is byte-unchanged, and a suppressed
         *    popup arms NO timer and NO scroll listener, so it cannot open
         *    by itself for any reason.
         *
         * ⛔ THE PERMANENT `_signed_up` SUPPRESSION ABOVE IS **NOT** IN THIS
         *    SET AND STILL RETURNS. Somebody who already gave their email
         *    for this kit must not be asked for it again, so their click
         *    finds no JS opener and the link's own href carries them to the
         *    kit page — the pre-1.19.271 behaviour, unchanged.
         */
        var autoSuppressed = false;

        if (!forceOpen && withinCooldown) {
            autoSuppressed = true;
        }
        if (!forceOpen && readSession(SESSION_SHOWN) === '1') {
            autoSuppressed = true;
        }

        // SHARED SESSION-FREQUENCY GUARD. Only popups that declare
        // `sessionGuard` consult it, so the two pre-existing popups are
        // unaffected. `data-force-open` (a server-side validation bounce
        // back to a submitted form) still wins, exactly as before — a
        // visitor who just submitted must see their own error message.
        if (!forceOpen && config.sessionGuard.length) {
            for (var g = 0; g < config.sessionGuard.length; g++) {
                if (readSession(config.sessionGuard[g]) === '1') {
                    autoSuppressed = true;
                }
            }
        }

        // ---- A/B ASSIGNMENT (1.19.204) --------------------------------
        //
        // Runs BEFORE any listener is wired and long before `show()`, while
        // the element is still `hidden`. By the time anything can paint, the
        // losing variant is not in the document at all.
        var variantKey = '';
        var variantMeta = null;
        if (config.abTest) {
            variantKey = assignVariant(config.abTest);
            variantMeta = config.abTest.variants[variantKey] || null;

            var blocks = popup.querySelectorAll('[data-bhp-variant]');
            for (var b = 0; b < blocks.length; b++) {
                if (blocks[b].getAttribute('data-bhp-variant') !== variantKey) {
                    blocks[b].parentNode.removeChild(blocks[b]);
                }
            }

            if (config.abTest.field) {
                var stamp = popup.querySelector('[name="' + config.abTest.field + '"]');
                if (stamp) {
                    stamp.value = variantKey;
                }
            }
            popup.setAttribute('data-bhp-variant-assigned', variantKey);
        }

        var contentName = variantMeta && variantMeta.contentName ? String(variantMeta.contentName) : '';

        /**
         * Every event this popup pushes carries the same experiment fields —
         * one function, so `view`, `submit` and `success` can never disagree
         * about which variant a visitor saw.
         */
        function eventExtra(base) {
            var extra = base || {};
            if (variantKey) {
                extra.variant = variantKey;
            }
            if (contentName) {
                extra.content_name = contentName;
            }
            return extra;
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
                // 1.19.292: reflexive exit -> short cooldown. See SOFT_DISMISS_HOURS.
                close(true, 'soft');
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

        /*
         * 1.19.271: `openReason` is OPTIONAL and defaults to the automatic
         * path, so every existing caller behaves exactly as before. When it
         * is given it rides along on the view event as `trigger_source`, so
         * a popup opened because somebody clicked "Get the Free Kit" is
         * distinguishable in the dataLayer from one that opened on its own.
         * The EVENT NAME is deliberately unchanged — renaming it would split
         * one funnel's view count into two series and break every comparison
         * against the releases before this one.
         */
        function show(openReason) {
            lastFocused = document.activeElement;
            popup.hidden = false;
            popup.classList.add('is-open');
            document.addEventListener('keydown', trapFocus, true);

            var focusable = getFocusable();
            (focusable[0] || dialog).focus();

            writeSession(SESSION_SHOWN, '1');
            // Every popup that opens claims the shared session slot, so the
            // next capture surface (another popup, or the quiz modal) knows
            // not to stack on top of it.
            writeSession(SHARED_SESSION_SHOWN_KEY, '1');
            var viewExtra = { page_type: popup.getAttribute('data-page-type') || '' };
            if (openReason) {
                viewExtra.trigger_source = String(openReason);
            }
            pushEvent(config.source, eventName(config.eventPrefix, 'view'), eventExtra(viewExtra));
        }

        /*
         * `wasDismissed` keeps its original meaning — truthy means "the
         * visitor closed this, write a cooldown". 1.19.292 adds an OPTIONAL
         * second argument naming HOW they closed it, so every existing
         * caller and every future one behaves exactly as before unless it
         * opts in. `'soft'` is the only special value; anything else, including
         * omitting it entirely, is the deliberate 10-day dismissal.
         */
        function close(wasDismissed, dismissKind) {
            popup.hidden = true;
            popup.classList.remove('is-open');
            document.removeEventListener('keydown', trapFocus, true);

            if (wasDismissed) {
                var cooldownMs = dismissKind === 'soft'
                    ? SOFT_DISMISS_HOURS * 60 * 60 * 1000
                    : DISMISS_DAYS * 24 * 60 * 60 * 1000;
                var until = Date.now() + cooldownMs;
                writeLocal(STORAGE_DISMISSED_UNTIL, String(until));
                /*
                 * ⛔ THE CLOSE EVENT IS UNCHANGED — same name, same payload,
                 *    no new `dismiss_kind` dimension. Adding one here would
                 *    be a GTM/GA4 change, and GTM was another desk's lane;
                 *    two desks editing the same measurement surface in one
                 *    night is how a container gets a half-defined field.
                 *    Recommended as a FOLLOW-UP in the handoff instead,
                 *    because knowing the soft/hard split would tell us
                 *    whether 24h was the right call.
                 */
                pushEvent(config.source, eventName(config.eventPrefix, 'close'), eventExtra({ page_type: popup.getAttribute('data-page-type') || '' }));
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
                // 1.19.292: reflexive exit, and the most likely MIS-TAP of
                // the four close paths -> short cooldown.
                close(true, 'soft');
            });
        }
        if (form) {
            form.addEventListener('submit', function () {
                // Fires synchronously before the browser navigates away.
                writeSession(PENDING_SUBMIT_KEY, JSON.stringify({
                    eventPrefix: config.eventPrefix,
                    source: config.source,
                    storagePrefix: config.storagePrefix,
                    thankYouPath: config.thankYouPath,
                    variant: variantKey,
                    contentName: contentName
                }));
                pushEvent(config.source, eventName(config.eventPrefix, 'submit'), eventExtra({ page_type: popup.getAttribute('data-page-type') || '' }));
            });
        }

        if (forceOpen) {
            show();
            return;
        }

        // Three trigger modes, selected per popup via config.trigger.mode:
        //   'simple' — whichever of (timer OR scroll%) happens first, no gating.
        //   'gated'  — the scroll condition can't fire before a minimum
        //              engagement time, with a separate, longer, ungated
        //              fallback timer for visitors who never scroll that far.
        //   'exit'   — leave-intent only, and never before the dwell floor.
        var isMobile = window.matchMedia('(max-width: 767px)').matches;
        var triggerConfig = config.trigger || DEFAULT_CONFIG.trigger;
        var mode = triggerConfig.mode;
        if (mode !== 'simple' && mode !== 'exit') {
            mode = 'gated';
        }
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
            detachExitListeners();
        }

        /**
         * The side-cart drawer (bundle-drawer.js) and this popup engine are
         * independent scripts with independent timers -- nothing previously
         * stopped a popup's scroll/delay trigger from firing while a customer
         * had just opened the cart drawer (e.g. right after Add to Cart) and
         * was still browsing with it open. Reproduced live: add an item, keep
         * scrolling, and the popup opened on top of the still-open drawer.
         * Deferring to a short poll until the drawer closes is the minimal fix
         * that works for every popup sharing this engine without forking it.
         */
        function isDrawerOpen() {
            return document.body.classList.contains('bhp-drawer-open');
        }

        /**
         * A second popup on the same page must never paint over the first.
         * `hasActiveOverlay` mirrors the check quiz-modal.js already makes,
         * from the other direction.
         */
        function isAnotherOverlayOpen() {
            if (document.querySelector('.mariana-popup.is-open')) {
                return true;
            }
            if (document.body.classList.contains('bhp-quiz-modal-open')) {
                return true;
            }
            return false;
        }

        function trigger() {
            if (triggered) {
                return;
            }
            if (isDrawerOpen() || isAnotherOverlayOpen()) {
                window.setTimeout(trigger, 1000);
                return;
            }
            // Re-check the shared guard at fire time, not only at init: the
            // quiz modal or another popup may have opened in the seconds
            // between arming and firing.
            if (config.sessionGuard.length) {
                for (var g = 0; g < config.sessionGuard.length; g++) {
                    if (readSession(config.sessionGuard[g]) === '1') {
                        cleanupTriggers();
                        return;
                    }
                }
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

        // ---- EXIT-INTENT (mode 'exit') --------------------------------
        //
        // THE DWELL FLOOR IS THE GATE, NOT A SUGGESTION. `minTimeElapsed`
        // starts false in this mode and is set true in exactly one place —
        // the floor timer's callback. Every exit path below returns early
        // while it is false, so no leave-intent gesture in the first
        // `minDelay` milliseconds can open anything, on either device.
        //
        // Desktop: the pointer leaves through the TOP of the viewport
        // (clientY <= 0) with no relatedTarget, which is the browser-chrome
        // direction. Leaving through a side or the bottom is not treated as
        // exit intent.
        //
        // Mobile: pointers do not leave a phone viewport, so the fallback is
        // a fast UPWARD flick after the visitor has already read some depth
        // (`scrollPct`). Deliberately conservative — `upThresholdPx` of
        // travel inside `upWindowMs` — so ordinary re-reading does not fire
        // it. ⚠ The marketing desk recorded a preference AGAINST a
        // scroll-up trigger ("not a scroll-up hijack"); the build brief
        // specified one. Both are recorded; the threshold is set high
        // precisely because of that reservation. (⛔ 1.19.293
        // `CYCLE166-CX-ANNOTATION-STRIP`: this file is SERVED UNMINIFIED to
        // every visitor, so its comments are public. Internal call names and
        // internal draft filenames were removed from this block for that
        // reason and must not be reintroduced — see the note at the head of
        // this file.) Nothing here hijacks or blocks the scroll — the
        // listener is passive and never calls preventDefault().
        //
        // -------------------------------------------------------------
        // ⛔ 1.19.173 (2026-08-05) — TRIGGER HARDENING. Andrew Signore
        //    reported, current turn: "The exit pop up just popped up when I
        //    tried to click 'get the complete collection' on the complete
        //    collection page - I wasnt exiting the site".
        //
        //    REPRODUCED on staging at 390 x 844, and the cause is NOT
        //    ambiguous. `/complete-collection/` carries
        //    `<a href="#bhp-landing-pricing-card">Get the Complete
        //    Collection</a>`. Tapping it smooth-scrolls the page UPWARD from
        //    y≈3956 to y≈814 — roughly 3,100 px of upward travel delivered as
        //    ~25 scroll events. `upTravel` blew straight past
        //    `upThresholdPx` and the modal opened on a purchase CTA.
        //
        //    THE PRINCIPLE THE OLD CODE MISSED: scrolling is not evidence of
        //    intent unless the VISITOR did it. A page that scrolls itself in
        //    response to a tap — an anchor jump, a scroll-into-view, a drawer
        //    locking the body — must never be read as "leaving".
        //
        //    Four guards, all of them additive; the dwell floor, the session
        //    guard, the drawer/overlay deferral and the funnel storage keys
        //    are untouched:
        //
        //      G1 INTERACTION COOLDOWN. Any pointer/touch/keyboard activation
        //         of an interactive element starts `INTERACTION_COOLDOWN_MS`
        //         during which nothing can fire, on either device.
        //      G2 GESTURE REQUIREMENT (mobile). Upward travel only counts
        //         while a real finger gesture is in progress or its momentum
        //         is still running. Programmatic scroll produces no touch
        //         events, so it now accumulates nothing — the accumulator is
        //         RESET rather than merely ignored, so the tail of a
        //         programmatic scroll cannot be inherited by the next real
        //         gesture.
        //      G3 UPWARD POINTER VELOCITY (desktop). A `mouseout` with a null
        //         relatedTarget is also emitted when the element under a
        //         STATIONARY pointer is removed, hidden or re-rendered. A
        //         genuine leave has real upward movement immediately before
        //         it, so recent mousemove samples must show it.
        //      G4 NAVIGATION PENDING. Once a click has committed the page to
        //         a real navigation (or the form has been submitted), nothing
        //         may open. A modal racing an unload is never wanted.
        // -------------------------------------------------------------
        var exitAttached = false;
        var lastScrollY = window.scrollY || 0;
        var lastScrollAt = Date.now();
        var upTravel = 0;
        var upThresholdPx = typeof deviceConfig.upThresholdPx === 'number' ? deviceConfig.upThresholdPx : 400;
        var upWindowMs = typeof deviceConfig.upWindowMs === 'number' ? deviceConfig.upWindowMs : 600;

        // G1/G2/G3/G4 state.
        var INTERACTION_COOLDOWN_MS = typeof deviceConfig.interactionCooldownMs === 'number'
            ? deviceConfig.interactionCooldownMs : 1500;
        // How long after the last touch event upward travel still counts as
        // finger-driven. Covers the momentum tail of one real flick without
        // covering a smooth-scroll that started from a tap a second ago.
        var TOUCH_GESTURE_WINDOW_MS = typeof deviceConfig.touchWindowMs === 'number'
            ? deviceConfig.touchWindowMs : 700;
        // A genuine leave has movement within this window before the mouseout.
        var POINTER_SAMPLE_WINDOW_MS = 400;
        // Minimum net upward movement, in px, across that window.
        var POINTER_UP_MIN_PX = 12;

        var INTERACTIVE_SELECTOR = 'a, button, input, select, textarea, label, summary, form,' +
            ' [role="button"], [role="link"], [role="tab"], [role="menuitem"], [onclick], .btn,' +
            ' [data-bhp-quiz], [data-bhp-bundle-form], [data-bhp-add-to-cart]';

        // Finger travel, in px, past which a touch is a scroll rather than an
        // activation. Comfortably under every platform's own tap slop.
        var TOUCH_DRAG_SLOP_PX = 12;

        var lastInteractionAt = 0;
        var lastTouchActivityAt = 0;
        var navigationPending = false;
        var pointerSamples = [];
        var pendingTouchInteractionAt = 0;
        var touchStartPoint = null;

        function closestInteractive(node) {
            if (node && node.nodeType === 3) {
                node = node.parentNode;
            }
            if (!node || node.nodeType !== 1 || typeof node.closest !== 'function') {
                return null;
            }
            try {
                return node.closest(INTERACTIVE_SELECTOR);
            } catch (e) {
                return null;
            }
        }

        // G1 + G2 bookkeeping.
        //
        // ⚠ THE DISTINCTION HERE IS LOAD-BEARING, AND GETTING IT WRONG IN
        //   EITHER DIRECTION BREAKS SOMETHING:
        //
        //   - `touchmove` must NEVER start the cooldown. A bare finger-drag on
        //     body text IS the gesture this mode listens for; if dragging
        //     silenced the trigger, the mobile fallback could never fire at
        //     all.
        //   - `touchstart` / `pointerdown` / `mousedown` start the cooldown
        //     ONLY on an interactive target, for the same reason: a flick
        //     begins with a touchstart on ordinary content.
        //   - `click` and `keydown` always start it. A drag produces no click,
        //     so this cannot swallow a real flick — but a tap on a control
        //     with no semantic role still gets covered.
        //   - A DRAG IS NOT AN ACTIVATION. A `touchstart` on a link or card
        //     opens the cooldown, because it may be a tap. The moment the
        //     finger travels more than `TOUCH_DRAG_SLOP_PX`, it is a scroll —
        //     the browser itself will not dispatch a click for it — and that
        //     cooldown is WITHDRAWN. Without this, a phone page dense with
        //     links and cards has almost no surface a visitor can start a
        //     flick on, and the mobile fallback silently never fires.
        //     ⭐ Measured on staging, not reasoned about: a flick whose
        //     touchstart landed at (195,200) on `/complete-collection/` was
        //     suppressed even though its accumulated upward travel reached
        //     418px, past the 400px threshold.
        function noteInteraction(event) {
            var now = Date.now();
            var type = event ? event.type : '';

            if (type === 'touchstart') {
                lastTouchActivityAt = now;
                var start = event.touches && event.touches[0];
                touchStartPoint = start ? { x: start.clientX, y: start.clientY } : null;
                if (closestInteractive(event.target)) {
                    lastInteractionAt = now;
                    pendingTouchInteractionAt = now;
                }
                return;
            }

            if (type === 'touchmove') {
                lastTouchActivityAt = now;
                var moved = event.touches && event.touches[0];
                if (pendingTouchInteractionAt && touchStartPoint && moved) {
                    var dx = Math.abs(moved.clientX - touchStartPoint.x);
                    var dy = Math.abs(moved.clientY - touchStartPoint.y);
                    if (dx > TOUCH_DRAG_SLOP_PX || dy > TOUCH_DRAG_SLOP_PX) {
                        if (lastInteractionAt === pendingTouchInteractionAt) {
                            lastInteractionAt = 0;
                        }
                        pendingTouchInteractionAt = 0;
                    }
                }
                return;
            }

            if (type === 'touchend') {
                lastTouchActivityAt = now;
                pendingTouchInteractionAt = 0;
                touchStartPoint = null;
                return;
            }

            if (type === 'click' || type === 'keydown' || closestInteractive(event.target)) {
                lastInteractionAt = now;
            }
        }

        // G4. Only a real navigation latches. A same-page hash link is NOT a
        // navigation — it is handled by the cooldown, which is what lets a
        // visitor who taps an in-page anchor still be offered the kit later
        // in the same visit.
        function noteNavigationIntent(event) {
            var node = event.target;
            if (node && node.nodeType === 3) {
                node = node.parentNode;
            }
            if (!node || node.nodeType !== 1 || typeof node.closest !== 'function') {
                return;
            }
            var link;
            try {
                link = node.closest('a[href]');
            } catch (e) {
                return;
            }
            if (!link) {
                return;
            }
            var href = link.getAttribute('href') || '';
            if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) {
                return;
            }
            if (link.target && link.target !== '_self') {
                return; // opens elsewhere; this page is not being left
            }
            navigationPending = true;
        }

        function noteFormSubmit() {
            navigationPending = true;
        }

        function withinInteractionCooldown() {
            return (Date.now() - lastInteractionAt) < INTERACTION_COOLDOWN_MS;
        }

        function withinTouchGesture() {
            return (Date.now() - lastTouchActivityAt) < TOUCH_GESTURE_WINDOW_MS;
        }

        function onPointerSample(event) {
            var now = Date.now();
            pointerSamples.push({ y: event.clientY, t: now });
            // Keep the buffer tiny; only the recent window is ever read.
            while (pointerSamples.length && (now - pointerSamples[0].t) > POINTER_SAMPLE_WINDOW_MS) {
                pointerSamples.shift();
            }
        }

        // G3.
        function hasUpwardPointerVelocity() {
            var now = Date.now();
            if (!pointerSamples.length) {
                return false;
            }
            var newest = pointerSamples[pointerSamples.length - 1];
            if ((now - newest.t) > 250) {
                return false; // pointer has been still; this is a DOM event, not a leave
            }
            var oldest = pointerSamples[0];
            if (newest === oldest) {
                return false; // a single sample proves no direction
            }
            return (oldest.y - newest.y) >= POINTER_UP_MIN_PX;
        }

        // Shared by both devices. Kept separate from the device-specific
        // handlers so the two can never drift apart.
        function exitBlocked() {
            if (!minTimeElapsed) {
                return true;
            }
            if (navigationPending) {
                return true;
            }
            if (withinInteractionCooldown()) {
                return true;
            }
            if (document.visibilityState && document.visibilityState !== 'visible') {
                return true;
            }
            return false;
        }

        function onMouseOut(event) {
            if (exitBlocked()) {
                return;
            }
            if (event.relatedTarget || event.toElement) {
                return; // moved to another element, not out of the document
            }
            // Document-leave semantics: the element the pointer left must still
            // be part of this document. A mouseout whose target has already
            // been detached is a DOM mutation under the cursor, not a leave.
            if (event.target && event.target.nodeType === 1 &&
                document.documentElement !== event.target &&
                !document.documentElement.contains(event.target)) {
                return;
            }
            if (typeof event.clientY !== 'number' || event.clientY > 0) {
                return; // left through a side or the bottom, not toward the chrome
            }
            if (typeof event.clientX === 'number' &&
                (event.clientX < 0 || event.clientX > window.innerWidth)) {
                return; // exited past a vertical edge, not through the top
            }
            if (!hasUpwardPointerVelocity()) {
                return; // G3 — no real upward movement preceded this event
            }
            trigger();
        }

        function onExitScroll() {
            var now = Date.now();
            var y = window.scrollY || 0;
            var delta = lastScrollY - y; // positive = scrolled up
            var elapsed = now - lastScrollAt;

            lastScrollY = y;
            lastScrollAt = now;

            // G1 + G2 + G4. Scrolling that is not finger-driven, or that
            // happens in the shadow of a tap, contributes NOTHING and clears
            // what came before it.
            if (navigationPending || withinInteractionCooldown() || !withinTouchGesture()) {
                upTravel = 0;
                return;
            }

            if (elapsed > upWindowMs) {
                upTravel = 0;
            }
            upTravel = delta > 0 ? upTravel + delta : 0;

            if (exitBlocked()) {
                return;
            }
            if (typeof scrollPct === 'number' && getScrollPercent() < scrollPct) {
                return; // has not read far enough for "leaving" to mean anything
            }
            if (upTravel >= upThresholdPx) {
                trigger();
            }
        }

        function attachExitListeners() {
            if (exitAttached) {
                return;
            }
            exitAttached = true;

            // G1/G4 — always attached, both devices, capture phase so a
            // handler that stops propagation cannot hide the interaction.
            document.addEventListener('pointerdown', noteInteraction, true);
            document.addEventListener('mousedown', noteInteraction, true);
            document.addEventListener('touchstart', noteInteraction, true);
            document.addEventListener('touchmove', noteInteraction, true);
            document.addEventListener('touchend', noteInteraction, true);
            document.addEventListener('click', noteInteraction, true);
            document.addEventListener('keydown', noteInteraction, true);
            document.addEventListener('click', noteNavigationIntent, true);
            document.addEventListener('submit', noteFormSubmit, true);

            if (isMobile) {
                window.addEventListener('scroll', onExitScroll, { passive: true });
            } else {
                document.addEventListener('mousemove', onPointerSample, { passive: true });
                document.addEventListener('mouseout', onMouseOut);
            }
        }

        function detachExitListeners() {
            if (!exitAttached) {
                return;
            }
            exitAttached = false;
            document.removeEventListener('pointerdown', noteInteraction, true);
            document.removeEventListener('mousedown', noteInteraction, true);
            document.removeEventListener('touchstart', noteInteraction, true);
            document.removeEventListener('touchmove', noteInteraction, true);
            document.removeEventListener('touchend', noteInteraction, true);
            document.removeEventListener('click', noteInteraction, true);
            document.removeEventListener('keydown', noteInteraction, true);
            document.removeEventListener('click', noteNavigationIntent, true);
            document.removeEventListener('submit', noteFormSubmit, true);
            window.removeEventListener('scroll', onExitScroll);
            document.removeEventListener('mousemove', onPointerSample);
            document.removeEventListener('mouseout', onMouseOut);
        }

        /*
         * ⭐ 1.19.271 — THE EXPLICIT OPENER.
         *
         * Registered on the element itself rather than in a module-level map
         * so that two popups on one page cannot collide, and so the delegated
         * listener at the bottom of this file has ONE thing to test for: if
         * this function is absent, there is no popup to open and the anchor's
         * own `href` does its job. That absence is the whole no-JS / no-popup
         * fallback, and it is why the server still renders a real `href` on
         * every CTA this release touches.
         *
         * It bypasses `trigger()` deliberately. `trigger()` re-checks the
         * shared session guard and defers behind the cart drawer, both of
         * which are politeness rules for an UNINVITED overlay. A click is an
         * invitation, so the only thing left to check is that the popup is
         * not already open. It still claims `triggered` and clears the timers,
         * so an auto-open cannot arrive on top of it seconds later.
         */
        popup.bhpOpenExplicitly = function (reason) {
            if (popup.classList.contains('is-open')) {
                return false;
            }
            triggered = true;
            cleanupTriggers();
            show(reason || 'explicit');
            return true;
        };

        if (autoSuppressed) {
            // Dismissed, already seen this session, or another capture
            // surface got there first. NOTHING is armed: no timer, no scroll
            // listener, no exit listener. The popup exists only for an
            // explicit click from here on.
            window.addEventListener('pagehide', cleanupTriggers);
            return;
        }

        if (mode === 'simple') {
            // Timer and scroll race unconditionally; whichever fires first wins.
            if (typeof deviceConfig.delay === 'number') {
                minTimeTimerId = window.setTimeout(trigger, deviceConfig.delay);
            }
            if (typeof scrollPct === 'number') {
                window.addEventListener('scroll', onScroll, { passive: true });
            }
        } else if (mode === 'exit') {
            // Listeners attach immediately so depth and scroll velocity are
            // measured from the first moment, but `minTimeElapsed` keeps the
            // gate shut until the floor elapses. There is NO fallback timer
            // in this mode by design: an exit-intent popup that opens on a
            // timer is just a timed popup.
            attachExitListeners();
            minTimeTimerId = window.setTimeout(function () {
                minTimeTimerId = null;
                minTimeElapsed = true;
            }, typeof deviceConfig.minDelay === 'number' ? deviceConfig.minDelay : 20000);
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

        window.addEventListener('pagehide', cleanupTriggers);
    }

    var popups = document.querySelectorAll('[data-bhp-popup]');
    for (var i = 0; i < popups.length; i++) {
        initPopup(popups[i]);
    }

    /*
     * ═══════════════════════════════════════════════════════════════════
     * ⭐ 1.19.271 — "LESS STEPS": A KIT CTA OPENS THE KIT POPUP.
     * ═══════════════════════════════════════════════════════════════════
     *
     * ⭐ Andrew Signore, verbatim (relayed as first-hand founder wording):
     *    "When someone on the blog hits Get the free kit - it should be a
     *    pop up where they can immediately subscribe- not send them to the
     *    reluctant reader page. Less steps."
     *
     * PROGRESSIVE ENHANCEMENT, AND IT IS THE LOAD-BEARING PART.
     *   The server renders an ORDINARY ANCHOR with a REAL `href` to the kit
     *   page. This listener upgrades it. Every one of the following falls
     *   back to plain navigation rather than to a dead control:
     *     - this script did not load, or threw before this line;
     *     - no popup is rendered on this surface (the server decides that,
     *       and it deliberately does not render one on selling pages);
     *     - the visitor already signed up, so `initPopup()` returned before
     *       registering an opener;
     *     - the popup is already open.
     *
     * ⛔ NOT CAPTURE-PHASE, AND PROPAGATION IS NEVER HALTED. The
     *    contextual-CTA analytics binder in `nav.js` listens for the same
     *    click through `data-bhp-event`. `preventDefault()` cancels the
     *    NAVIGATION only; both listeners still run, so
     *    `contextual_cta_click` fires exactly as it did before this release
     *    and the Lead event path is untouched.
     *
     * ⚠ THE METHOD THAT WOULD HALT IT IS DELIBERATELY NOT NAMED IN THIS
     *   COMMENT. `tests/test-kit-cta-popup.php` asserts that its name does
     *   not appear anywhere in this file, and a prose mention would break
     *   that guard while changing no behaviour. Two guards in neighbouring
     *   files have already been caught by exactly this mistake, which is why
     *   the rule is described here instead of quoted.
     *
     * ⛔ MODIFIER AND MIDDLE CLICKS ARE LEFT ALONE. Ctrl/Cmd/Shift/Alt click
     *    and any non-primary button keep their browser meaning — open in a
     *    new tab or window — because taking that away from a link that still
     *    has a real destination would be a regression, not a simplification.
     */
    function findPopupTrigger(node) {
        while (node && node !== document) {
            if (node.getAttribute && node.getAttribute('data-bhp-popup-open')) {
                return node;
            }
            node = node.parentNode;
        }
        return null;
    }

    document.addEventListener('click', function (event) {
        if (event.defaultPrevented) {
            return;
        }
        if (typeof event.button === 'number' && event.button !== 0) {
            return;
        }
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        var trigger = findPopupTrigger(event.target);
        if (!trigger) {
            return;
        }

        var popup = document.getElementById(trigger.getAttribute('data-bhp-popup-open'));
        if (!popup || typeof popup.bhpOpenExplicitly !== 'function') {
            // No popup on this page, or it is permanently suppressed for this
            // visitor. Let the anchor navigate; it still has a real href.
            return;
        }

        // The popup exists and can be opened, so this click belongs to it —
        // including the already-open case, where navigating away from an open
        // capture form would be the worst of both outcomes.
        event.preventDefault();
        popup.bhpOpenExplicitly(trigger.getAttribute('data-bhp-popup-open-reason') || 'cta_click');
    });
})();
