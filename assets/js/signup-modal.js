/**
 * CTA-triggered signup modal controller — theme 1.19.225, 2026-08-13,
 * `CYCLE158-LD-SIGNUP-POPUP` iteration 3.
 *
 * Drives `template-parts/acquisition/signup-modal.php`. A visitor clicks a
 * funnel CTA that used to scroll down to the inline capture panel; instead
 * the modal opens over the page.
 *
 * ⭐ 1.19.225 — WHERE THE CARET GOES DEPENDS ON THE POINTER, AND THAT IS THE
 *    WHOLE OF THIS ITERATION. On a fine-pointer device the caret is in the
 *    email field on open, unchanged from 1.19.223. On a coarse-pointer device
 *    it is in the dialog container, so no virtual keyboard is summoned by the
 *    same gesture that opened the box. See `hasCoarsePointer()` below for the
 *    predicate, the hybrid-device ruling and the real-device defect that
 *    forced it.
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

  /**
   * ⭐ 1.19.225 — THE ONE PREDICATE THAT DECIDES WHETHER WE AUTOFOCUS.
   *
   * WHY THIS EXISTS. Iteration 2 focused the email input on every device.
   * Andrew Signore re-tested on his own iPhone (iOS Safari, real hardware,
   * screenshot supplied) and it failed: one tap on the CTA opened the dialog
   * AND summoned the keyboard AND the autofill accessory bar in a single
   * gesture. Between them they took roughly the lower 60% of the screen, the
   * dialog was left taller than what remained visible, and the visitor landed
   * in a cramped, clipped box with the eyebrow gone, the cover half hidden
   * and only the word "Kit" left of the headline. Verbatim: "you click the
   * CTA and this happens - not a good UX."
   *
   * ⛔ THE SIMULATED-visualViewport QA PASSED WHILE THE REAL DEVICE FAILED.
   *    A synthetic `visualViewport.height` shrink reproduces the geometry and
   *    reproduces NONE of iOS's own behaviour: it does not raise an accessory
   *    bar, it does not run WebKit's scroll-the-focused-input-into-view pass,
   *    and it does not reflow a `position: fixed` element mid-keyboard-
   *    animation. Simulation is evidence about layout arithmetic and is NOT
   *    evidence about a device. That is recorded here, not just in the QA
   *    packet, because the next person to touch this file will be tempted by
   *    the same shortcut.
   *
   * THE PREDICATE, AND WHY IT IS `any-pointer` RATHER THAN `pointer`:
   *
   *   `(pointer: coarse)`     describes the PRIMARY pointing device. A Windows
   *                           laptop with a touchscreen reports `fine`, so a
   *                           hybrid device would still be autofocused — and a
   *                           hybrid device with the keyboard folded away is
   *                           exactly a tablet.
   *   `(any-pointer: coarse)` is true when ANY available pointing device is
   *                           coarse. Phones and tablets match. Touch laptops
   *                           and 2-in-1s match. A plain desktop with a mouse
   *                           or trackpad does not.
   *
   * So hybrids get the SAFE behaviour (no autofocus), which is the deliberate
   * choice: the cost of not autofocusing on a machine that has a hardware
   * keyboard is one extra click; the cost of autofocusing on a machine that
   * raises a virtual keyboard is the defect above.
   *
   * ⛔ NO USER-AGENT SNIFFING. Nothing here reads `navigator.userAgent`, and
   *    nothing here names a vendor, a browser or an OS.
   *
   * The `mq.media !== 'not all'` test is the feature detection: an engine that
   * cannot parse `any-pointer` returns a MediaQueryList whose `media` is
   * normalised to `'not all'` and whose `matches` is a meaningless `false`.
   * Trusting that `false` would autofocus every visitor on such an engine,
   * which is the failure we are removing — so we fall through to touch-point
   * detection instead, which is capability detection too, not sniffing.
   *
   * Evaluated on every open rather than cached at load: a tablet can gain and
   * lose a pointing device (a keyboard folio, a paired mouse) inside one page
   * view, and this call costs nothing.
   */
  function hasCoarsePointer() {
    if (typeof window.matchMedia === 'function') {
      var mq = window.matchMedia('(any-pointer: coarse)');
      if (mq && mq.media !== 'not all') {
        return !!mq.matches;
      }
    }
    if (typeof navigator !== 'undefined' && (navigator.maxTouchPoints || 0) > 0) {
      return true;
    }
    return 'ontouchstart' in window;
  }

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
    var submitBtn = form ? form.querySelector('.acquisition-form__submit') : null;

    var leadOffer = modal.getAttribute('data-bhp-lead-offer') || '';
    var audience = modal.getAttribute('data-bhp-form-audience') || '';
    var pageType = modal.getAttribute('data-page-type') || '';

    var isOpen = false;
    var lastFocused = null;
    var openedFrom = '';
    // 'email' on a fine-pointer device, 'dialog' on a coarse-pointer one.
    // Reported inside the EXISTING `signup_modal_opened` event — no new event
    // name is minted for this, deliberately.
    var initialFocus = '';

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
      var active = document.activeElement;

      /*
       * ⭐ 1.19.225 — THE TRAP NOW HOLDS FROM THE DIALOG CONTAINER TOO.
       *
       * On a coarse-pointer device the initial focus is the dialog element
       * itself, which carries `tabindex="-1"` and is therefore deliberately
       * NOT in `focusable`. Forward Tab from there happened to work (the
       * browser's own order walks into the dialog's children), but SHIFT+Tab
       * walked BACKWARDS out of the dialog and into the page behind it — a
       * real trap leak, introduced the moment initial focus stopped being the
       * email input. It is closed here rather than by adding the dialog to
       * the focusable set, because a `tabindex="-1"` element must not be a
       * tab stop the visitor can cycle through.
       *
       * `indexOf === -1` also covers the case where focus has ended up on
       * <body> (Safari after a click on a non-focusable region).
       */
      if (active === dialog || focusable.indexOf(active) === -1) {
        event.preventDefault();
        (event.shiftKey ? last : first).focus();
        return;
      }

      if (event.shiftKey && active === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && active === last) {
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

    /**
     * ⭐ 1.19.225 — IS THE VISITOR ACTUALLY TYPING?
     *
     * This is the gate that makes the whole iteration-3 fix hold together.
     * The capture-visibility scroll below is CORRECT while a virtual keyboard
     * is up and WRONG at every other moment: with no keyboard, scrolling the
     * dialog down to the subscribe button is precisely what hides the
     * eyebrow, the headline and the cover — the exact clipped view Andrew
     * photographed. So the adjustment now runs only while a field inside this
     * dialog holds focus, which is the only state in which a virtual keyboard
     * can be on screen.
     */
    function aFieldIsFocused() {
      var active = document.activeElement;
      if (!active || !form || !form.contains(active)) {
        return false;
      }
      return /^(input|textarea|select)$/i.test(active.tagName || '');
    }

    /**
     * ⭐ 1.19.225 — THE EMAIL FIELD **AND** THE SUBSCRIBE BUTTON STAY ABOVE
     *    THE KEYBOARD. Supersedes 1.19.224's `ensureSubmitVisible()`, which
     *    optimised for the button alone.
     *
     * 1.19.224 scrolled the dialog until the submit button's bottom edge sat
     * inside the dialog's visible region, and called that on open. Two things
     * were wrong with it, both proven by the real-device failure:
     *
     *   1. It ran on OPEN, before any keyboard existed. On a short phone the
     *      dialog can overflow by a few pixels with no keyboard at all, and
     *      the response — scroll to the button — threw away the top of the
     *      offer for no benefit. It is now gated on `aFieldIsFocused()`.
     *   2. It anchored on ONE element. With the keyboard AND iOS's autofill
     *      accessory bar up, "the button is visible" and "the field I am
     *      typing in is visible" are different claims, and the visitor needs
     *      both.
     *
     * WHAT IT DOES NOW: treats [email input top → submit button bottom] as one
     * band and moves the dialog's own scroll region by the SMALLEST amount
     * that brings that band inside the visible box. If the band is taller than
     * the box — a landscape phone, a very short viewport, 200% zoom — it pins
     * the field being typed in to the top of the visible region and leaves the
     * button one short scroll away INSIDE the dialog, which is reachable,
     * rather than clipping the dialog, which is not.
     *
     * ⛔ IT NEVER FIGHTS iOS. WebKit runs its own scroll-the-focused-input-
     *    into-view pass when a keyboard appears, and two controllers pushing
     *    the same scroll container in the same frame is what produces the
     *    jitter this release is meant to remove. Three things prevent that:
     *    the adjustment is MINIMAL (zero when the band is already inside the
     *    box), it has a 2px dead band so sub-pixel disagreement is not a
     *    correction, and it is scheduled AFTER the browser's own pass rather
     *    than racing it (see `scheduleCaptureCheck()`).
     *
     * ⛔ THE PAGE IS NEVER SCROLLED. `dialog.scrollTop` moves the dialog's own
     *    overflow region and nothing else. The visitor's position on the page
     *    behind the modal is not touched here or anywhere in this file.
     */
    function ensureCaptureVisible() {
      if (!isOpen || !aFieldIsFocused()) {
        return;
      }
      if (dialog.scrollHeight <= dialog.clientHeight + 1) {
        return;
      }

      var topAnchor = emailInput || submitBtn;
      var bottomAnchor = submitBtn || emailInput;
      if (!topAnchor || !bottomAnchor) {
        return;
      }

      var PAD_TOP = 8;
      var PAD_BOTTOM = 12;
      var DEAD_BAND = 2;

      var box = dialog.getBoundingClientRect();
      var topBox = topAnchor.getBoundingClientRect();
      var bottomBox = bottomAnchor.getBoundingClientRect();

      var bandHeight = bottomBox.bottom - topBox.top;
      var roomAvailable = box.height - PAD_TOP - PAD_BOTTOM;

      // The band does not fit. Show the field being typed in; the button is
      // reachable by scrolling the dialog, and the dialog is never clipped.
      if (bandHeight > roomAvailable) {
        var offset = topBox.top - (box.top + PAD_TOP);
        if (Math.abs(offset) > DEAD_BAND) {
          dialog.scrollTop += offset;
        }
        return;
      }

      var below = bottomBox.bottom - (box.bottom - PAD_BOTTOM);
      if (below > DEAD_BAND) {
        dialog.scrollTop += below;
        return;
      }

      var above = (box.top + PAD_TOP) - topBox.top;
      if (above > DEAD_BAND) {
        dialog.scrollTop -= above;
      }
    }

    /**
     * Runs the check AFTER the browser has had its own go, never against it.
     * `requestAnimationFrame` covers the same-frame case; the timeout covers
     * iOS, where the keyboard and the accessory bar animate in over roughly a
     * quarter of a second and `visualViewport` reports intermediate heights
     * the whole way. Both passes are idempotent — the function is a
     * minimal-correction, so a second run on a settled layout does nothing.
     */
    function scheduleCaptureCheck() {
      if (typeof window.requestAnimationFrame === 'function') {
        window.requestAnimationFrame(ensureCaptureVisible);
      } else {
        ensureCaptureVisible();
      }
      window.setTimeout(ensureCaptureVisible, 320);
    }

    function syncViewport() {
      if (!vv || !isOpen) {
        return;
      }
      modal.style.setProperty('--bhp-modal-vv-height', vv.height + 'px');
      modal.style.setProperty('--bhp-modal-vv-top', (vv.offsetTop || 0) + 'px');
      modal.style.setProperty('--bhp-modal-vv-left', (vv.offsetLeft || 0) + 'px');
      ensureCaptureVisible();
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

      /*
       * ⭐ 1.19.225 — WHERE FOCUS LANDS, AND WHY IT IS NOT THE SAME EVERYWHERE.
       *
       * DESKTOP (fine pointer, no virtual keyboard): focus goes to the EMAIL
       * INPUT, exactly as 1.19.223 shipped it. Andrew's original "instant
       * type" requirement is untouched where it works — the caret is in the
       * field, the visitor types, presses Enter, done. The optional first-name
       * field above it is deliberately skipped.
       *
       * TOUCH / COARSE POINTER: focus goes to the DIALOG CONTAINER, which
       * already carries `tabindex="-1"` in signup-modal.php. No virtual
       * keyboard is summoned, so the modal opens fully visible and stable —
       * eyebrow, headline, cover, both fields and the subscribe button all on
       * screen, which is what the iteration-2 fold measurements actually
       * proved before the keyboard wrecked them. The visitor then taps the
       * email field themselves: one natural tap, the standard iOS pattern,
       * and iOS runs its own well-tuned scroll-into-view for a tap it
       * initiated.
       *
       * ⭐ FOCUSING THE CONTAINER IS NOT "NO FOCUS MANAGEMENT". It keeps the
       *    focus trap live, keeps ESC working, moves the screen reader's
       *    cursor into the dialog so `aria-labelledby`/`aria-describedby` are
       *    announced, and preserves focus return to the triggering CTA on
       *    close. A modal that left focus on the page behind it would be a
       *    different and worse defect.
       *
       * `preventScroll` keeps the page behind the modal exactly where it was
       * on engines that honour it.
       */
      var coarse = hasCoarsePointer();
      initialFocus = coarse ? 'dialog' : 'email';

      var target = coarse ? dialog : (emailInput || getFocusable()[0] || dialog);
      try {
        target.focus({ preventScroll: true });
      } catch (e) {
        target.focus();
      }

      // No-op on touch: no field is focused, so the offer opens at its top
      // rather than scrolled down to the button. That difference IS the fix.
      ensureCaptureVisible();

      // Claims the shared session slot, so the exit-intent engine's
      // `sessionGuard` blocks it for the rest of this tab's session. This is
      // the mechanism `mariana-popup.js` already defines; no new key.
      writeSession(SHARED_SESSION_SHOWN_KEY, '1');

      /*
       * ⛔ THE EVENT NAME IS UNCHANGED, AND SO IS EVERY EXISTING PARAMETER.
       *    `initial_focus` is a new PARAM inside the event that already
       *    shipped, which is what the brief asked for: no new event name, so
       *    nothing downstream in GA4/GTM has to be reconfigured to keep
       *    counting opens. It is 'email' or 'dialog' and carries no personal
       *    data, no device identifier and no user-agent string.
       */
      pushEvent('signup_modal_opened', {
        source_cta: openedFrom,
        open_reason: reason || 'cta_click',
        lead_offer: leadOffer,
        audience: audience,
        placement: 'signup_modal',
        page_type: pageType,
        initial_focus: initialFocus
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

      // A reopen starts at the top of the offer, never wherever
      // ensureSubmitVisible() left the previous session's scroll region.
      dialog.scrollTop = 0;

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

    /*
     * ⭐ 1.19.225 — THE VISITOR TAPPED THE FIELD, SO THE KEYBOARD IS COMING.
     *
     * `visualViewport`'s `resize` is the authoritative signal and it does fire
     * on iOS and Android — but it fires several times during the keyboard's
     * animation and, on some Android builds with the resizes-visual behaviour
     * disabled, it does not fire at all. This listener is the belt to that
     * braces: whenever a field inside this dialog takes focus, the capture
     * band is re-checked once the browser has finished its own scroll pass.
     *
     * ⛔ It never focuses anything. It observes focus and adjusts the dialog's
     *    own scroll region, which is the same minimal, dead-banded correction
     *    `syncViewport()` makes.
     */
    dialog.addEventListener('focusin', function (event) {
      var el = event.target;
      if (!el || !/^(input|textarea|select)$/i.test(el.tagName || '')) {
        return;
      }
      scheduleCaptureCheck();
    });

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
      coverWarmed: false,
      hasForceOpen: modal.getAttribute('data-force-open') === '1'
    };
  }

  /**
   * ⭐ 1.19.224 — THE COVER IS IN CACHE BEFORE THE DIALOG OPENS.
   *
   * The modal root is `hidden`, i.e. `display: none` from the moment the
   * document parses, and no image inside a non-rendered subtree is fetched.
   * That is exactly what the funnel pages want — the cover must cost their
   * LCP nothing — but it means a cold visitor would watch the cover appear a
   * beat after the box does.
   *
   * So the bytes are warmed on the visitor's FIRST hover, focus or touch of
   * any CTA that opens this dialog, which on every input method precedes the
   * click that opens it. Exactly one request, only for visitors who show
   * intent, and never on page load.
   *
   * ⛔ IT WARMS THE WEBP ONLY. The <picture> element's PNG is a fallback for
   *    engines that cannot decode WebP; on those engines this preload is a
   *    no-op that fails silently and the PNG loads at open time. Warming both
   *    would double the request for every visitor to save a rounding error of
   *    them.
   */
  function warmCover(api) {
    if (!api || api.coverWarmed) {
      return;
    }
    api.coverWarmed = true;
    var src = api.el.getAttribute('data-bhp-cover-preload');
    if (!src) {
      return;
    }
    var warm = new Image();
    warm.decoding = 'async';
    warm.src = src;
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

        // One-shot cover warm-up on the first sign of intent, whatever the
        // input method. `once` means these unbind themselves; `passive` means
        // a touch listener can never delay a scroll.
        var warm = function () { warmCover(api); };
        trigger.addEventListener('pointerenter', warm, { once: true, passive: true });
        trigger.addEventListener('focus', warm, { once: true, passive: true });
        trigger.addEventListener('touchstart', warm, { once: true, passive: true });

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
