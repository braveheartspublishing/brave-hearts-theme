/**
 * CTA-triggered signup modal controller — theme 1.19.223, 2026-08-13,
 * `CYCLE158-LD-SIGNUP-POPUP`.
 *
 * Drives `template-parts/acquisition/signup-modal.php`. A visitor clicks a
 * funnel CTA that used to scroll down to the inline capture panel; instead
 * the modal opens with the caret already in the email field.
 *
 * ---------------------------------------------------------------------
 * WHY THIS IS A SEPARATE FILE FROM `mariana-popup.js`, STATED SO IT IS NOT
 * RE-LITIGATED AS A FORK.
 *
 * `.claude/rules/funnels.md` says: "don't fork the engine to add a third
 * funnel; extend the config schema instead." That rule governs FUNNELS on
 * the automatic lead-popup engine, and this is neither.
 *
 *   - No new funnel. Every modal here carries the SAME lead-magnet key, the
 *     SAME audience type and the SAME thank-you redirect as the inline panel
 *     on the same page. No new storage prefix and no new analytics prefix is
 *     minted anywhere in this file.
 *   - No trigger. `mariana-popup.js` is, almost in its entirety, a trigger
 *     engine: dwell floors, scroll depth, exit intent, four hardening guards,
 *     dismissal cooldowns and session frequency caps. EVERY ONE of those is
 *     wrong for a modal a visitor deliberately opened. A 10-day dismissal
 *     cooldown on a button the visitor just pressed would mean the button
 *     silently does nothing for ten days. Extending that engine's config to
 *     say "ignore all of your own logic" is not reuse; it is a second engine
 *     wearing the first one's name.
 *
 * WHAT *IS* REUSED, DELIBERATELY:
 *   - The `.mariana-popup` markup and stylesheet, so the modal looks like
 *     every other modal on the site and no new visual spec is invented.
 *   - `template-parts/acquisition/signup-form.php`, the ONE submission
 *     handler. This file never intercepts, serialises, AJAXes or otherwise
 *     touches a form submission. `submit` is observed, never prevented.
 *   - The existing collision vocabulary. Because the root element carries
 *     `.mariana-popup` and gains `.is-open`, `mariana-popup.js`'s
 *     `isAnotherOverlayOpen()` and `quiz-modal.js`'s `hasActiveOverlay()`
 *     BOTH already see this modal and defer to it, with zero changes to
 *     either file. That is the collision control in requirement 3, obtained
 *     from code that already shipped rather than from new code.
 * ---------------------------------------------------------------------
 * ⛔ FUNNEL ISOLATION. This file reads NOTHING and writes exactly one
 *    storage key: `bhp_popup_shown_session`, the SHARED session frequency
 *    flag that `mariana-popup.js` already defines and that every popup which
 *    opens already writes. It carries no funnel identity, no audience, no
 *    offer and no personal data, and it is sessionStorage, so it dies with
 *    the tab. Writing it is what makes "opening the signup modal suppresses
 *    exit-intent" true through the mechanism that already exists instead of
 *    a new one. NOTHING under `bhp_parent_popup_*` or `bhp_mariana_popup_*`
 *    is read or written here, in either direction.
 *
 * ⛔ NO PERSONAL DATA IS EVER STORED CLIENT-SIDE.
 *
 * PROGRESSIVE ENHANCEMENT. Every CTA keeps `href="#free"`. This file adds a
 * click handler that calls preventDefault() only once it has resolved a real
 * modal element for that CTA. With JS off, or if the modal element is absent
 * (e.g. the lead magnet has no PDF yet and the page rendered its "coming
 * soon" block instead), the CTA is an ordinary anchor and still scrolls to
 * the inline panel exactly as it did before this release.
 */
(function () {
  'use strict';

  // Defined by mariana-popup.js as SHARED_SESSION_SHOWN_KEY. Repeated here
  // rather than exported because the two files are independent scripts with
  // no module boundary between them; the STRING is the contract.
  var SHARED_SESSION_SHOWN_KEY = 'bhp_popup_shown_session';

  var BODY_OPEN_CLASS = 'bhp-signup-modal-open';

  var FOCUSABLE_SELECTOR = 'a[href], button:not([disabled]), input:not([disabled]),' +
    ' select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

  function pushEvent(name, payload) {
    if (typeof window.dataLayer === 'undefined' || !Array.isArray(window.dataLayer)) {
      return;
    }
    window.dataLayer.push(Object.assign({
      event: name,
      page_path: window.location.pathname || ''
    }, payload || {}));
  }

  function writeSession(key, value) {
    try {
      window.sessionStorage.setItem(key, value);
    } catch (e) {
      /* sessionStorage unavailable (private mode, etc.) — fail silently. */
    }
  }

  /**
   * Another overlay already owns the screen. Mirrors the checks the two
   * existing engines make, from this side, so three modals can never stack.
   * The signup modal LOSES this race rather than queueing: it was opened by
   * a deliberate click, and a click that lands while the quiz modal or the
   * cart drawer is open is not a request to paint a third layer on top.
   */
  function anotherOverlayIsOpen(self) {
    var open = document.querySelectorAll('.mariana-popup.is-open');
    for (var i = 0; i < open.length; i++) {
      if (open[i] !== self) {
        return true;
      }
    }
    if (document.body.classList.contains('bhp-quiz-modal-open')) {
      return true;
    }
    if (document.body.classList.contains('bhp-drawer-open')) {
      return true;
    }
    if (document.querySelector('.bhp-cart-drawer.is-open')) {
      return true;
    }
    return false;
  }

  function initModal(modal) {
    var dialog = modal.querySelector('.mariana-popup__dialog');
    if (!dialog) {
      return null;
    }

    var overlay = modal.querySelector('[data-bhp-signup-modal-overlay]');
    var closeBtn = modal.querySelector('[data-bhp-signup-modal-close]');
    var form = modal.querySelector('form.acquisition-form');
    var emailInput = form ? form.querySelector('input[type="email"]') : null;

    var leadOffer = modal.getAttribute('data-bhp-lead-offer') || '';
    var audience = modal.getAttribute('data-bhp-form-audience') || '';
    var pageType = modal.getAttribute('data-page-type') || '';

    var isOpen = false;
    var lastFocused = null;
    var openedFrom = '';

    function getFocusable() {
      var nodes = dialog.querySelectorAll(FOCUSABLE_SELECTOR);
      return Array.prototype.filter.call(nodes, function (el) {
        return el.offsetParent !== null || el === document.activeElement;
      });
    }

    function onKeydown(event) {
      if (event.key === 'Escape' || event.key === 'Esc') {
        event.preventDefault();
        close('escape');
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

    /**
     * MOBILE ON-SCREEN KEYBOARD.
     *
     * `.mariana-popup` is `position: fixed; inset: 0`, which sizes it to the
     * LAYOUT viewport. On iOS Safari the layout viewport does NOT shrink when
     * the keyboard appears — only the VISUAL viewport does — so a dialog
     * centred against `inset: 0` is centred against an area roughly half of
     * which is behind the keyboard, and the email input a visitor was just
     * asked to type in can end up under it. `100dvh` does not fix this
     * either: the dynamic viewport unit tracks browser chrome, not the
     * virtual keyboard.
     *
     * The only instrument that reports the keyboard is `visualViewport`. When
     * it exists, the modal is pinned to the visual viewport's own box while
     * open, so "centred" means centred in what the visitor can actually see.
     * When it does not exist, nothing is written and the CSS behaves exactly
     * as it does for every other `.mariana-popup` today.
     *
     * ⛔ Nothing here scrolls the PAGE. The visitor's scroll position is
     *    never touched by this file, in either direction.
     */
    var vv = window.visualViewport || null;

    function syncViewport() {
      if (!vv || !isOpen) {
        return;
      }
      modal.style.setProperty('--bhp-modal-vv-height', vv.height + 'px');
      modal.style.setProperty('--bhp-modal-vv-top', (vv.offsetTop || 0) + 'px');
      modal.style.setProperty('--bhp-modal-vv-left', (vv.offsetLeft || 0) + 'px');
    }

    function clearViewport() {
      modal.style.removeProperty('--bhp-modal-vv-height');
      modal.style.removeProperty('--bhp-modal-vv-top');
      modal.style.removeProperty('--bhp-modal-vv-left');
    }

    function attachViewportSync() {
      if (!vv) {
        return;
      }
      modal.classList.add('has-visual-viewport');
      syncViewport();
      vv.addEventListener('resize', syncViewport);
      vv.addEventListener('scroll', syncViewport);
    }

    function detachViewportSync() {
      if (!vv) {
        return;
      }
      vv.removeEventListener('resize', syncViewport);
      vv.removeEventListener('scroll', syncViewport);
      modal.classList.remove('has-visual-viewport');
      clearViewport();
    }

    function open(trigger, sourceLabel, reason) {
      if (isOpen) {
        return;
      }
      if (anotherOverlayIsOpen(modal)) {
        return;
      }

      // The launcher element itself, not document.activeElement: Safari does
      // not move focus to a link or button on click, so activeElement would
      // sometimes be <body> and focus would be returned to nowhere.
      lastFocused = trigger || null;
      openedFrom = sourceLabel || '';

      isOpen = true;
      modal.hidden = false;
      modal.classList.add('is-open');
      document.body.classList.add(BODY_OPEN_CLASS);
      if (trigger && trigger.setAttribute) {
        trigger.setAttribute('aria-expanded', 'true');
      }

      attachViewportSync();
      document.addEventListener('keydown', onKeydown, true);

      // ⭐ THE REQUIREMENT, IN ONE LINE. Focus goes to the email input, not
      //    to the dialog and not to the close button, so the visitor can
      //    type immediately. The name field above it is optional and is
      //    deliberately skipped. `preventScroll` keeps the page behind the
      //    modal exactly where it was on engines that honour it.
      var target = emailInput || getFocusable()[0] || dialog;
      try {
        target.focus({ preventScroll: true });
      } catch (e) {
        target.focus();
      }

      // Claims the shared session slot, so the exit-intent engine's
      // `sessionGuard` blocks it for the rest of this tab's session. This is
      // the mechanism `mariana-popup.js` already defines; no new key.
      writeSession(SHARED_SESSION_SHOWN_KEY, '1');

      pushEvent('signup_modal_opened', {
        source_cta: openedFrom,
        open_reason: reason || 'cta_click',
        lead_offer: leadOffer,
        audience: audience,
        placement: 'signup_modal',
        page_type: pageType
      });
    }

    function close(reason) {
      if (!isOpen) {
        return;
      }
      isOpen = false;

      modal.classList.remove('is-open');
      modal.hidden = true;
      document.body.classList.remove(BODY_OPEN_CLASS);

      detachViewportSync();
      document.removeEventListener('keydown', onKeydown, true);

      if (lastFocused && lastFocused.setAttribute) {
        lastFocused.setAttribute('aria-expanded', 'false');
      }

      pushEvent('signup_modal_closed', {
        source_cta: openedFrom,
        close_reason: reason || 'unknown',
        lead_offer: leadOffer,
        audience: audience,
        placement: 'signup_modal',
        page_type: pageType
      });

      // Focus returns to the CTA that opened the dialog. `preventScroll`
      // matters here: without it, returning focus to a CTA that is now off
      // screen scrolls the page to it, which is the "the page moved on me"
      // symptom the quiz modal already had to fix once.
      if (lastFocused && typeof lastFocused.focus === 'function') {
        try {
          lastFocused.focus({ preventScroll: true });
        } catch (e) {
          lastFocused.focus();
        }
      }
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        close('close_button');
      });
    }
    if (overlay) {
      overlay.addEventListener('click', function () {
        close('overlay');
      });
    }

    /*
     * A native POST that navigates away. This listener OBSERVES it and never
     * calls preventDefault() — the existing signup-form.php submission is
     * completely untouched, including its nonce, its redirect and every
     * analytics event downstream of it. `acquisition-form-ux.js` still
     * applies its own busy state to this form like any other.
     */
    if (form) {
      form.addEventListener('submit', function () {
        pushEvent('signup_modal_submit', {
          source_cta: openedFrom,
          lead_offer: leadOffer,
          audience: audience,
          placement: 'signup_modal',
          page_type: pageType
        });
      });
    }

    return {
      el: modal,
      open: open,
      close: close,
      hasForceOpen: modal.getAttribute('data-force-open') === '1'
    };
  }

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  ready(function () {
    var registry = {};
    var modals = document.querySelectorAll('[data-bhp-signup-modal]');
    for (var i = 0; i < modals.length; i++) {
      var api = initModal(modals[i]);
      if (api) {
        registry[modals[i].id] = api;
      }
    }
    if (!Object.keys(registry).length) {
      return;
    }

    var triggers = document.querySelectorAll('[data-bhp-signup-modal-open]');
    for (var t = 0; t < triggers.length; t++) {
      (function (trigger) {
        var targetId = trigger.getAttribute('data-bhp-signup-modal-open');
        var api = targetId ? registry[targetId] : null;
        if (!api) {
          // No modal for this CTA — leave it as the plain anchor it already
          // is. This is the "coming soon" path and the no-modal fallback.
          return;
        }
        // Announce the control's real behaviour only once it is genuinely
        // wired. Doing this in JS rather than in PHP means a no-JS visitor
        // is never told about a dialog that cannot open for them.
        trigger.setAttribute('aria-haspopup', 'dialog');
        trigger.setAttribute('aria-controls', targetId);
        trigger.setAttribute('aria-expanded', 'false');

        trigger.addEventListener('click', function (event) {
          // Never swallow a modified click — a visitor deliberately opening
          // the panel in a new tab still gets the anchor.
          if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ||
              (typeof event.button === 'number' && event.button > 0)) {
            return;
          }
          event.preventDefault();
          api.open(trigger, trigger.getAttribute('data-bhp-signup-modal-source') || '', 'cta_click');
        });
      })(triggers[t]);
    }

    /*
     * Validation bounce. The server redirected back with this modal's own
     * form id after a failed submission, so the visitor's error message is
     * inside a dialog that is currently closed. Reopen it, with no trigger
     * element, and let the error be read.
     */
    Object.keys(registry).forEach(function (id) {
      var api = registry[id];
      if (!api.hasForceOpen) {
        return;
      }
      var fallbackTrigger = document.querySelector('[data-bhp-signup-modal-open="' + id + '"]');
      api.open(fallbackTrigger, 'validation_bounce', 'validation_error');
    });
  });
})();
