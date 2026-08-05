/**
 * Sitewide quiz launcher modal (2026-07-17, timer/scroll auto-open follow-up).
 *
 * Generic dialog/focus-trap behavior only -- the quiz itself (questions,
 * routing, its own analytics) lives entirely in audience-quiz.js and is
 * untouched by this file. This script only opens/closes the wrapper,
 * manages focus, and (as of this follow-up) decides WHEN to open it
 * automatically; audience-quiz.js's own DOMContentLoaded handler already
 * initializes whatever [data-bhp-quiz] elements exist in the document,
 * including the one rendered inside this modal, so no second init path
 * is needed here.
 *
 * One modal per launcher, matched via the launcher's aria-controls
 * attribute pointing at the modal's id (both ids are made unique per
 * render in template-parts/components/quiz-entry-cta.php via
 * wp_unique_id()) -- safe even if a future page ever renders more than
 * one launcher.
 *
 * Scroll preservation (2026-07-29): the visitor's exact scroll position is
 * captured immediately before the modal opens and re-asserted on close.
 * Without this, returning focus to the launcher -- which sits near the
 * footer and is therefore off-screen after an automatic open -- made the
 * browser scroll it into view, dumping the visitor at the bottom of a page
 * they were reading halfway down. Focus still returns to the launcher (that
 * part is correct dialog behavior); it just uses focus({preventScroll:true})
 * where supported, with the explicit restore covering both the older-browser
 * fallback path and any engine that scrolls asynchronously. Applies to all
 * four dismissal routes: close button, Escape, backdrop, and the result
 * screen's "Keep browsing this page" action.
 *
 * State: closing the modal (Escape, backdrop click, close button, "Keep
 * browsing this page") does NOT reset quiz progress -- the underlying
 * audience-quiz markup is only
 * hidden, never removed or re-rendered, so re-opening resumes exactly
 * where the visitor left off. Only the quiz's own Restart control clears
 * answers. Navigating to a different page naturally resets everything on
 * the next load.
 *
 * Auto-open trigger (2026-07-17 follow-up): on pages where the modal's
 * root carries data-bhp-quiz-autoopen="true" (computed server-side by
 * bhp_should_autoopen_quiz() in functions.php -- same eligible-page set as
 * the manual launcher, minus /teachers/, which already runs a separate
 * automatic overlay of its own), the modal opens itself once per browser
 * session on whichever happens first:
 *   - 9000ms after the page becomes ready, or
 *   - the visitor reaches 40% scroll depth.
 * A sessionStorage flag (BHP_AUTO_SHOWN_KEY) records that the quiz has
 * already been shown (by any method -- manual or automatic) so it never
 * auto-opens more than once per session, and so a fresh session (new tab,
 * new incognito window) can auto-open again. Manually clicking the
 * launcher before either trigger fires cancels both immediately.
 *
 * ---------------------------------------------------------------------
 * DWELL FLOOR (2026-08-04, theme 1.19.167). The paragraph above is kept
 * verbatim as the original design; the two numbers in it (9000ms -- which
 * had already become 8000ms in code -- and 40%) and the words "whichever
 * happens first" are SUPERSEDED by this block. Do not read them as current.
 *
 * Owner ruling, Andrew Signore, 2026-08-04: the auto-open "needs more time
 * for people to peruse the site" -- "20 seconds please".
 *
 * The two triggers no longer race in time. There is now a hard MINIMUM
 * DWELL FLOOR of AUTO_OPEN_DELAY_MS (20000ms) measured from the moment the
 * trigger arms, and NO automatic open can happen before it, by any path:
 *
 *   - The floor timer is the only thing that can turn `dwellFloorReached`
 *     true, and every automatic open goes through fireAutoTrigger(), which
 *     is only ever called (a) from that timer's own callback, or (b) from
 *     evaluateScrollTrigger() AFTER it has confirmed the floor is reached.
 *   - Reaching SCROLL_THRESHOLD (now 0.60) BEFORE the floor does not open
 *     anything. It records intent (`scrollIntentPending`) and the open then
 *     happens when the floor is reached, attributed honestly as
 *     'scroll_60_after_floor' rather than 'timer'.
 *   - The one-shot evaluation at arm time (a restored scroll position, an
 *     anchor link, or a page short enough to already be past 60%) runs
 *     through the same gate, so it can only ever record intent. This is the
 *     path that used to let a short page open the modal near-instantly.
 *   - Reaching 0.60 AFTER the floor would open immediately. Stated honestly:
 *     with the floor timer firing the open itself the moment the floor is
 *     met, this branch is a DEFENSIVE GUARD and does not execute on the
 *     normal path -- the timer's callback sets dwellFloorReached and calls
 *     fireAutoTrigger() in the same tick, which detaches the scroll
 *     listener. It is kept so that the gate, not the call site, is what
 *     enforces the floor: any future caller of evaluateScrollTrigger()
 *     inherits the correct behaviour instead of having to remember it.
 *
 * A manual launcher click is NOT subject to the floor -- it is the
 * visitor's own action, not an automatic open.
 *
 * Unchanged by the dwell-floor work: the once-per-session sessionStorage
 * key, the overlay-pending retry (MutationObserver + slow poll), the
 * pagehide cleanup, scroll restoration, and the quiz_auto_trigger_armed /
 * quiz_auto_trigger_cancelled / quiz_modal_opened / quiz_modal_closed
 * events. The only analytics changes are honest ones: open_reason
 * 'scroll_40' no longer exists and is replaced by 'scroll_60_after_floor'
 * (so cancel_reason 'opened_scroll_40' becomes
 * 'opened_scroll_60_after_floor'), quiz_auto_trigger_armed now also carries
 * dwell_floor_ms and scroll_threshold, and one new observability-only event
 * quiz_auto_trigger_scroll_intent fires when 60% is reached while the floor
 * is still holding the open back.
 * ---------------------------------------------------------------------
 */
(function () {
  'use strict';

  var AUTO_SHOWN_KEY = 'bhp_quiz_auto_shown';
  // Wave 1 (2026-08-04, theme 1.19.168) — SHARED SESSION-FREQUENCY GUARD.
  //
  // Written by mariana-popup.js whenever ANY popup sharing that engine
  // opens. Read here so the quiz modal never auto-opens on top of, or
  // immediately after, a capture modal the visitor has already been shown
  // in this session. One capture modal per session, whichever got there
  // first.
  //
  // ⚠️ THIS IS A REAL BEHAVIOUR CHANGE AND IS STATED RATHER THAN BURIED:
  //    before this, hasActiveOverlay() only deferred the quiz WHILE another
  //    overlay was painted, and then opened it once that overlay closed —
  //    i.e. the visitor got both, back to back. Now the second one does not
  //    open at all for the rest of the session.
  //
  // ⛔ It carries no funnel identity, no audience, no offer and no personal
  //    data, and it is sessionStorage. It cannot move parent or teacher
  //    suppression state, which still lives entirely under each funnel's
  //    own prefix (`.claude/rules/funnels.md`).
  //
  // ⛔ A MANUAL launcher click is NOT affected. This gate is only read by
  //    the automatic path, exactly like AUTO_SHOWN_KEY.
  var SHARED_POPUP_SHOWN_KEY = 'bhp_popup_shown_session';
  // Minimum dwell floor AND the timer trigger -- one number, deliberately.
  // Nothing automatic may open the modal before this many milliseconds have
  // elapsed since the trigger armed. Owner ruling 2026-08-04: "20 seconds".
  var AUTO_OPEN_DELAY_MS = 20000;
  // Scroll depth that counts as engagement. Reaching it before the floor
  // records intent; it does NOT open anything early.
  var SCROLL_THRESHOLD = 0.60;
  // A blocked trigger is never abandoned. It becomes PENDING and is
  // re-checked when the blocking overlay goes away — driven by a
  // MutationObserver plus a slow safety poll, so there is no rapid loop and
  // no silent give-up. (The previous build consumed the trigger up-front and
  // gave up permanently after 5 x 1s, which killed both triggers for the
  // rest of the visit.)
  var OVERLAY_POLL_MS = 1000;

  function pushEvent(eventName, payload) {
    if (typeof window.dataLayer === 'undefined' || !Array.isArray(window.dataLayer)) {
      return;
    }
    window.dataLayer.push(Object.assign({ event: eventName }, payload || {}));
  }

  function hasBeenShownThisSession() {
    try {
      if (sessionStorage.getItem(AUTO_SHOWN_KEY) === '1') {
        return true;
      }
      // Another capture modal already had this session.
      return sessionStorage.getItem(SHARED_POPUP_SHOWN_KEY) === '1';
    } catch (e) {
      return false;
    }
  }

  function markShownThisSession() {
    try {
      sessionStorage.setItem(AUTO_SHOWN_KEY, '1');
      // Claim the shared session slot too, so a popup sharing
      // mariana-popup.js does not open after the quiz has been seen.
      sessionStorage.setItem(SHARED_POPUP_SHOWN_KEY, '1');
    } catch (e) {
      // Storage unavailable (e.g. some private-browsing modes) -- fail
      // open rather than throw; worst case the quiz can auto-open more
      // than once in that edge case, which is not harmful.
    }
  }

  // An element counts as "rendering" only if it actually occupies space.
  // Measuring the element itself (not an ancestor) matters here: WPConsent's
  // banner is position:fixed, so every one of its ancestors -- including the
  // #wpconsent-container host -- collapses to a 0x0 box even while the banner
  // is painted full-width across the viewport.
  function isRendered(el) {
    var rect = el.getBoundingClientRect();
    return rect.width > 0 && rect.height > 0;
  }

  // Detects a *visible* WPConsent cookie banner or preferences modal.
  //
  // 2026-07-29 fix. The previous implementation checked
  //   consent.children.length > 0 && consent.offsetWidth > 0 && consent.offsetHeight > 0
  // and could never return true, for two independent reasons verified on
  // staging 1.19.93:
  //   1. WPConsent renders its entire banner into an *open shadow root* on
  //      #wpconsent-container, so `.children` (light DOM only) is always 0.
  //   2. The banner is position:fixed, so the host element's own box is
  //      always 0x0 -- offsetWidth/offsetHeight are 0 even when the banner
  //      is fully painted at z-index 900000.
  // The net effect was that the guard silently never fired, and the quiz
  // auto-opened on top of a visible cookie-consent dialog: two competing
  // role="dialog" overlays, each running its own focus management. That
  // collision -- not a defect in this file's focus trap -- is what made Tab
  // appear to "escape" the quiz dialog into #wpconsent-container. (The
  // escape is reported against the host because document.activeElement
  // retargets to the shadow host when focus is inside a shadow tree.)
  //
  // Deliberately NOT fixed by trapping focus harder: the visitor must stay
  // able to reach and answer the consent banner. The correct behavior is to
  // not open a second dialog over it in the first place, which is what the
  // existing retry loop in attemptAutoOpen() already handles once this
  // returns an accurate answer.
  //
  // Still best-effort: the plugin's internals aren't owned by this theme, so
  // a closed shadow root or renamed containers would make this return false,
  // in which case behavior is no worse than before the fix.
  //
  // Scoped to WPConsent's two *blocking* overlays rather than "any rendered
  // descendant of the container". The container also holds the optional
  // floating consent-settings button (#wpconsent-consent-floating, currently
  // display:none on this site). That button is a small persistent affordance,
  // not an overlay -- treating it as a conflict would permanently suppress
  // the quiz auto-open on every page if it is ever enabled.
  //
  // Each root is measured through its descendants, not itself: the banner
  // holder is 1280x0 while the position:fixed .wpconsent-banner inside it is
  // 1280x65 and fully painted.
  var CONSENT_OVERLAY_SELECTOR = '#wpconsent-banner-holder, #wpconsent-preferences-modal';

  function hasVisibleConsentUI() {
    var consent = document.getElementById('wpconsent-container');
    if (!consent) {
      return false;
    }
    var roots = [consent];
    if (consent.shadowRoot) {
      roots.push(consent.shadowRoot);
    }
    for (var i = 0; i < roots.length; i++) {
      var overlays = roots[i].querySelectorAll(CONSENT_OVERLAY_SELECTOR);
      for (var j = 0; j < overlays.length; j++) {
        if (isRendered(overlays[j])) {
          return true;
        }
        var parts = overlays[j].querySelectorAll('*');
        for (var k = 0; k < parts.length; k++) {
          if (isRendered(parts[k])) {
            return true;
          }
        }
      }
    }
    return false;
  }

  // Best-effort overlay-conflict checks. Reused element/class names are
  // the theme's own existing components (side-cart drawer, the legacy
  // mariana-popup engine still used by the teacher popup).
  function hasActiveOverlay(modal) {
    if (!modal.hasAttribute('hidden')) {
      return true; // this modal itself is already open
    }
    if (document.querySelector('.mariana-popup.is-open')) {
      return true;
    }
    if (document.querySelector('.bhp-cart-drawer.is-open')) {
      return true;
    }
    if (hasVisibleConsentUI()) {
      return true;
    }
    return false;
  }

  var FOCUSABLE_SELECTOR = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

  // The theme sets `html { scroll-behavior: smooth }` sitewide, which would
  // turn a scroll-position RESTORE into a visible animated scroll -- exactly
  // the "page moved on me" symptom this restore exists to prevent. Suppress
  // it for the duration of the jump, then put the declared value back so
  // normal in-page anchor links keep animating as designed. Setting the
  // inline property to '' restores the stylesheet value; it does not force
  // 'auto' permanently.
  function scrollToInstant(x, y) {
    var html = document.documentElement;
    var previous = html.style.scrollBehavior;
    html.style.scrollBehavior = 'auto';
    window.scrollTo(x, y);
    html.style.scrollBehavior = previous;
  }

  function initLauncher(launcher) {
    var modalId = launcher.getAttribute('aria-controls');
    var modal = modalId ? document.getElementById(modalId) : null;
    if (!modal) {
      return;
    }

    var dialog = modal.querySelector('.bhp-quiz-modal__dialog');
    var closeBtn = modal.querySelector('[data-bhp-quiz-modal-close]');
    var backdrop = modal.querySelector('[data-bhp-quiz-modal-backdrop]');
    var entryLocation = modal.getAttribute('data-bhp-quiz-modal-location') || '';
    var autoOpenEligible = modal.getAttribute('data-bhp-quiz-autoopen') === 'true';
    var lastFocused = null;
    var isOpen = false;
    var openScrollX = 0;
    var openScrollY = 0;

    var autoTriggerArmed = false;
    var timerId = null;
    var scrollTicking = false;
    // Dwell floor (2026-08-04). `dwellFloorReached` is set true in exactly
    // one place -- the floor timer's callback -- and is the sole gate every
    // automatic open passes through. `scrollIntentPending` remembers that
    // the visitor crossed SCROLL_THRESHOLD while the floor was still
    // holding, so the eventual open can be attributed to the scroll rather
    // than to the timer.
    var dwellFloorReached = false;
    var scrollIntentPending = false;
    // A trigger that fired but was blocked by another overlay. Held here
    // until the overlay clears rather than being thrown away.
    var pendingReason = null;
    var overlayObserver = null;
    var overlayPollId = null;

    function getFocusable() {
      return Array.prototype.slice.call(dialog.querySelectorAll(FOCUSABLE_SELECTOR)).filter(function (el) {
        return el.offsetParent !== null;
      });
    }

    function onKeydown(event) {
      if (event.key === 'Escape') {
        event.preventDefault();
        closeModal('escape');
        return;
      }
      if (event.key !== 'Tab') {
        return;
      }
      var focusable = getFocusable();
      if (!focusable.length) {
        event.preventDefault();
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

    function openModal(reason) {
      if (isOpen) {
        return;
      }
      isOpen = true;
      cancelAutoTrigger('opened_' + (reason || 'manual'));
      markShownThisSession();

      // Store the launcher itself, not document.activeElement -- Safari
      // does not move focus to a button on click by default, so relying on
      // "whatever has focus at open time" would sometimes capture <body>
      // instead of the launcher. The requirement is specifically to return
      // focus to the exact launcher that opened the dialog, so reference it
      // directly. For automatic opens there was no preceding click, but the
      // launcher is still the correct element to return focus to on close.
      lastFocused = launcher;
      openScrollX = window.scrollX || window.pageXOffset || 0;
      openScrollY = window.scrollY || window.pageYOffset || 0;

      modal.hidden = false;
      modal.classList.add('is-open');
      document.body.classList.add('bhp-quiz-modal-open');
      launcher.setAttribute('aria-expanded', 'true');
      pushEvent('quiz_modal_opened', { entry_location: entryLocation, open_reason: reason || 'manual' });

      var focusable = getFocusable();
      (closeBtn || focusable[0] || dialog).focus();

      document.addEventListener('keydown', onKeydown, true);
    }

    function closeModal(reason) {
      if (!isOpen) {
        return;
      }
      isOpen = false;

      modal.classList.remove('is-open');
      modal.hidden = true;
      document.body.classList.remove('bhp-quiz-modal-open');
      launcher.setAttribute('aria-expanded', 'false');
      pushEvent('quiz_modal_closed', { entry_location: entryLocation, close_reason: reason || 'unknown' });

      document.removeEventListener('keydown', onKeydown, true);

      var focusTarget = lastFocused && typeof lastFocused.focus === 'function' ? lastFocused : launcher;
      try {
        focusTarget.focus({ preventScroll: true });
      } catch (e) {
        // Older browsers do not support FocusOptions. The explicit
        // scroll restoration below still prevents the launcher from
        // pulling an auto-opened visitor down the page.
        focusTarget.focus();
      }

      // Returning focus to the off-screen launcher after an automatic
      // open would otherwise scroll the visitor to the quiz CTA near the
      // footer. Restore the exact page position captured when the modal
      // opened, regardless of how it was dismissed.
      scrollToInstant(openScrollX, openScrollY);
      // Second pass on the next frame: removing the body scroll lock and
      // un-hiding/hiding the dialog can settle layout a frame later, and
      // some engines apply focus-driven scrolling asynchronously. Re-asserting
      // the same coordinates is a no-op when the first pass already held.
      if (typeof window.requestAnimationFrame === 'function') {
        window.requestAnimationFrame(function () {
          scrollToInstant(openScrollX, openScrollY);
        });
      }
    }

    // --- Auto-open trigger (timer + scroll depth) ---------------------

    /**
     * Scroll depth = distance travelled / distance available:
     *
     *     scrollY / (documentHeight - viewportHeight)
     *
     * The previous build used `(scrollY + viewportHeight) / documentHeight`,
     * which is "how much of the document has been seen", not how far the
     * visitor has scrolled — on a page whose viewport is already >= 40% of
     * the document that is true at scrollY 0, so it fired instantly; on
     * long pages it crossed at a different point than intended. A page with
     * no scrollable distance yields 0 rather than a divide-by-zero.
     */
    function scrollDepth() {
      var scrollY = window.scrollY || window.pageYOffset || 0;
      var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
      var documentHeight = document.documentElement.scrollHeight || 0;
      var scrollable = documentHeight - viewportHeight;
      if (scrollable <= 0) {
        return 0;
      }
      return scrollY / scrollable;
    }

    /**
     * The dwell-floor gate. This is the ONLY place a scroll can reach
     * fireAutoTrigger(), and it cannot do so until the floor has elapsed.
     *
     * Called from three places, all of which route through the same gate:
     * the coalesced scroll handler, the one-shot evaluation at arm time,
     * and the floor timer is deliberately NOT one of them (it calls
     * fireAutoTrigger directly, because by definition the floor is met).
     */
    function evaluateScrollTrigger() {
      if (!autoTriggerArmed) {
        return;
      }
      if (scrollDepth() < SCROLL_THRESHOLD) {
        return;
      }
      if (!dwellFloorReached) {
        // HARD FLOOR. Record the intent and wait -- never open early.
        if (!scrollIntentPending) {
          scrollIntentPending = true;
          pushEvent('quiz_auto_trigger_scroll_intent', {
            entry_location: entryLocation,
            scroll_threshold: SCROLL_THRESHOLD,
            dwell_floor_ms: AUTO_OPEN_DELAY_MS,
          });
        }
        return;
      }
      fireAutoTrigger('scroll_60_after_floor');
    }

    function onScroll() {
      if (scrollTicking) {
        return;
      }
      scrollTicking = true;
      // rAF is used only to coalesce scroll events when it is available.
      // It is deliberately NOT required: in a background/throttled tab rAF
      // can be suspended indefinitely, and the threshold check must not
      // depend on a frame ever being painted.
      var run = function () {
        scrollTicking = false;
        evaluateScrollTrigger();
      };
      if (typeof window.requestAnimationFrame === 'function') {
        window.requestAnimationFrame(run);
        window.setTimeout(function () {
          if (scrollTicking) {
            run();
          }
        }, 250);
      } else {
        window.setTimeout(run, 0);
      }
    }

    function cancelAutoTrigger(cancelReason) {
      // A pending (overlay-blocked) trigger must be torn down too — e.g.
      // the visitor opened the quiz manually while it was waiting.
      var hadPending = pendingReason !== null;
      pendingReason = null;
      scrollIntentPending = false;
      clearPendingWatch();

      if (!autoTriggerArmed) {
        if (hadPending) {
          pushEvent('quiz_auto_trigger_cancelled', { entry_location: entryLocation, cancel_reason: cancelReason });
        }
        return;
      }
      autoTriggerArmed = false;
      if (timerId !== null) {
        clearTimeout(timerId);
        timerId = null;
      }
      window.removeEventListener('scroll', onScroll);
      pushEvent('quiz_auto_trigger_cancelled', { entry_location: entryLocation, cancel_reason: cancelReason });
    }

    /**
     * Stop watching for a blocking overlay. Safe to call repeatedly and
     * always called on unload so nothing is left running.
     */
    function clearPendingWatch() {
      if (overlayObserver) {
        overlayObserver.disconnect();
        overlayObserver = null;
      }
      if (overlayPollId !== null) {
        clearInterval(overlayPollId);
        overlayPollId = null;
      }
    }

    /**
     * A trigger fired but an overlay is in the way. Hold it as PENDING and
     * open as soon as the overlay clears — never abandon it, and never
     * mark the session as shown just because it is waiting.
     *
     * The MutationObserver reacts promptly to the overlay closing; the 1s
     * interval is a low-frequency safety net for overlay changes a DOM
     * mutation can't describe (e.g. a shadow-DOM consent widget). Both are
     * torn down the moment the quiz opens, is cancelled, or the page
     * unloads, so there is no runaway polling.
     */
    function watchForOverlayClear() {
      if (overlayObserver || overlayPollId !== null) {
        return; // already watching
      }

      var tryOpen = function () {
        if (!pendingReason || hasBeenShownThisSession() || isOpen) {
          clearPendingWatch();
          return;
        }
        if (hasActiveOverlay(modal)) {
          return; // still blocked — keep waiting
        }
        var reason = pendingReason;
        pendingReason = null;
        clearPendingWatch();
        openModal(reason);
      };

      if (typeof window.MutationObserver === 'function') {
        overlayObserver = new window.MutationObserver(tryOpen);
        overlayObserver.observe(document.body, {
          attributes: true,
          childList: true,
          subtree: true,
          attributeFilter: ['class', 'hidden', 'style', 'aria-hidden'],
        });
      }
      overlayPollId = window.setInterval(tryOpen, OVERLAY_POLL_MS);
    }

    function attemptAutoOpen(reason) {
      if (hasBeenShownThisSession() || isOpen) {
        return;
      }
      if (hasActiveOverlay(modal)) {
        // Hold the trigger instead of consuming it.
        pendingReason = reason;
        watchForOverlayClear();
        return;
      }
      openModal(reason);
    }

    function fireAutoTrigger(reason) {
      if (!autoTriggerArmed) {
        return;
      }
      // Disarm the *other* trigger source so only one can fire, but the
      // trigger itself is not discarded — if an overlay blocks the open it
      // becomes pending and is retried until the overlay clears.
      autoTriggerArmed = false;
      if (timerId !== null) {
        clearTimeout(timerId);
        timerId = null;
      }
      window.removeEventListener('scroll', onScroll);
      attemptAutoOpen(reason);
    }

    function armAutoTrigger() {
      if (!autoOpenEligible || hasBeenShownThisSession()) {
        return;
      }
      autoTriggerArmed = true;
      // The dwell floor. This callback is the only thing that can release
      // the gate, which is what makes "never before 20s" true by
      // construction rather than by every caller remembering to check.
      timerId = window.setTimeout(function () {
        timerId = null;
        dwellFloorReached = true;
        fireAutoTrigger(scrollIntentPending ? 'scroll_60_after_floor' : 'timer');
      }, AUTO_OPEN_DELAY_MS);
      window.addEventListener('scroll', onScroll, { passive: true });
      pushEvent('quiz_auto_trigger_armed', {
        entry_location: entryLocation,
        dwell_floor_ms: AUTO_OPEN_DELAY_MS,
        scroll_threshold: SCROLL_THRESHOLD,
      });

      // The visitor may already be past the scroll threshold when the page
      // arms — a restored scroll position, an anchor link, or a page short
      // enough that 60% is already behind them. No scroll event would ever
      // fire in that case, so evaluate the threshold once now. Since the
      // floor cannot possibly have elapsed at arm time, this call can only
      // ever RECORD INTENT — it can no longer open the modal instantly,
      // which is exactly the short-page defect the floor exists to fix.
      evaluateScrollTrigger();

      // Never leave an observer or interval running after the page goes.
      window.addEventListener('pagehide', function () {
        pendingReason = null;
        scrollIntentPending = false;
        clearPendingWatch();
        if (timerId !== null) {
          clearTimeout(timerId);
          timerId = null;
        }
      });
    }

    launcher.addEventListener('click', function () {
      openModal('manual');
    });
    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        closeModal('close_button');
      });
    }
    if (backdrop) {
      backdrop.addEventListener('click', function () {
        closeModal('backdrop');
      });
    }
    // "Keep browsing this page" on the result screen (2026-07-29). Rendered
    // by the shared quiz component only when it is called with `in_modal`,
    // so this is a no-op on the homepage / canonical quiz page. It closes
    // through the exact same path as every other dismissal, which is what
    // guarantees identical scroll restoration -- no second close routine.
    var keepBrowsingBtn = modal.querySelector('[data-bhp-quiz-dismiss]');
    if (keepBrowsingBtn) {
      keepBrowsingBtn.addEventListener('click', function () {
        closeModal('keep_browsing');
      });
    }

    armAutoTrigger();
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bhp-quiz-launcher]').forEach(initLauncher);
  });
})();
