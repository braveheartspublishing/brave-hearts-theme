/**
 * Find Your Adventure — audience-routing quiz engine.
 *
 * Driven entirely by the data-bhp-quiz-config JSON on the root element
 * (same config-on-root-element pattern as mariana-popup.js), so this stays
 * a single reusable component rather than one-off per-page markup.
 *
 * Fires privacy-conscious analytics events only (no PII): quiz_viewed,
 * quiz_started, quiz_q1_answer, quiz_q2_answer, quiz_completed,
 * quiz_destination_click, quiz_abandoned, quiz_restarted (added 2026-07-17
 * sitewide-launcher follow-up — the Restart control previously fired no
 * event at all). Never captures email — the landing page it routes to
 * remains the one place lead capture happens.
 *
 * ⭐ REVISION B (P2-2, 2026-08-03) — THE TWO QUESTIONS SWAPPED JOBS, AND SO
 * DID THIS ENGINE. Q1 now asks WHAT IS HARD and routes nowhere; Q2 is the
 * router. Three things follow, and all three are load-bearing:
 *
 *   1. THIS FILE NO LONGER BUILDS ANY OPTION MARKUP. Both question screens
 *      are server-rendered by audience-quiz.php, because Q2's four answers
 *      are the four audiences and PHP already knows them. The innerHTML
 *      option-builder is gone, not disabled.
 *   2. `quiz_q1_answer` CANNOT CARRY `quiz_audience` ANY MORE. At Q1 the
 *      audience is genuinely unknown. It carries `quiz_pain` instead. Every
 *      EVENT NAME is unchanged — only that one payload key moved, and
 *      `quiz_intent` retires with the twelve per-answer options it named.
 *      ANALYTICS/EVENT_MATRIX.md holds no quiz events at all, so nothing
 *      canonical reads either key; historic values stay valid and must not
 *      be back-filled or reinterpreted.
 *   3. THE RESULT IS ALWAYS ROUTE-LEVEL. There are no per-answer overrides
 *      left to fall back from.
 *
 * ⛔ SUPERSEDED, recorded rather than deleted: "Per-answer results
 * (2026-07-29): the result headline/supporting text/CTA label — and
 * optionally the destination — are read from the SELECTED Q2 option first,
 * falling back to the route-level values." Those options no longer exist.
 *
 * Focus management (2026-07-29): advancing a step moves focus into the
 * newly revealed step (first Q2 option, then the result headline), so
 * keyboard and screen-reader users perceive the advance rather than being
 * silently dropped onto <body> when the previous step is hidden. All focus
 * calls use preventScroll where supported so nothing jumps the page.
 *
 * Optional intro-gate reveal (2026-07-17, homepage quiz-entry section): if
 * the root contains a [data-bhp-quiz-intro] block (rendered only when the
 * template part is called with `intro_gate` true), Q1 stays hidden until
 * its [data-bhp-quiz-start] button is clicked. That click fires its own
 * homepage_quiz_started event and otherwise just reuses showStep(1) below
 * — no separate quiz logic, no change to quiz_viewed through
 * quiz_abandoned.
 *
 * Sitewide quiz routing (2026-07-17): every event above now also carries
 * an `entry_location` field (config.entryLocation, set by the PHP template
 * part's `entry_location` arg -- 'homepage' or 'quiz_page' today) via the
 * pushQuizEvent() wrapper below, so the same component reports where it
 * was rendered without a second code path. The result CTA's outbound UTM
 * (utm_content) uses the same value. The sitewide entry CTA banner that
 * links to the canonical quiz page is a separate, much smaller component
 * (template-parts/components/quiz-entry-cta.php) that fires its own
 * quiz_cta_viewed / quiz_cta_clicked events through the theme's existing
 * generic data-bhp-event / data-bhp-impression-event handlers in nav.js --
 * no code here handles that CTA.
 *
 * The "Keep browsing this page" result action rendered inside the modal is
 * bound by quiz-modal.js, not here -- closing the dialog and restoring the
 * visitor's scroll position is dialog behavior, not quiz behavior.
 */
(function () {
  'use strict';

  function pushEvent(analyticsOn, event, payload) {
    if (!analyticsOn) {
      return;
    }
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(Object.assign({ event: event }, payload || {}));
  }

  function withUtm(url, utmParams) {
    try {
      var parsed = new URL(url, window.location.origin);
      Object.keys(utmParams || {}).forEach(function (key) {
        parsed.searchParams.set(key, utmParams[key]);
      });
      return parsed.toString();
    } catch (e) {
      return url;
    }
  }

  function initQuiz(root) {
    var configRaw = root.getAttribute('data-bhp-quiz-config');
    if (!configRaw) {
      return;
    }

    var config;
    try {
      config = JSON.parse(configRaw);
    } catch (e) {
      return;
    }

    var routes = config.routes || {};
    var utmParams = config.utmParams || {};
    var analyticsOn = !!config.analyticsOn;
    var entryLocation = config.entryLocation || 'quiz';
    var signupUrl = config.signupUrl || '';
    var signupNonce = config.signupNonce || '';

    // Merges entry_location into every quiz event payload (2026-07-17
    // sitewide quiz routing) without touching each individual pushEvent
    // call site below.
    function pushQuizEvent(event, payload) {
      pushEvent(analyticsOn, event, Object.assign({ entry_location: entryLocation }, payload || {}));
    }

    var stepEls = {
      1: root.querySelector('[data-bhp-quiz-step="1"]'),
      2: root.querySelector('[data-bhp-quiz-step="2"]'),
      result: root.querySelector('[data-bhp-quiz-step="result"]'),
    };
    var q1Options = root.querySelector('[data-bhp-quiz-q1]');
    var q2Options = root.querySelector('[data-bhp-quiz-q2]');
    var backBtn = root.querySelector('[data-bhp-quiz-back]');
    var restartBtn = root.querySelector('[data-bhp-quiz-restart]');
    var resultTitle = root.querySelector('[data-bhp-quiz-result-title]');
    var resultText = root.querySelector('[data-bhp-quiz-result-text]');
    var resultResource = root.querySelector('[data-bhp-quiz-result-resource]');
    var resultCta = root.querySelector('[data-bhp-quiz-result-cta]');
    // Inline result signup (2026-07-30).
    var signupForm = root.querySelector('[data-bhp-quiz-signup]');
    var signupSubmit = root.querySelector('[data-bhp-quiz-signup-submit]');
    var signupError = root.querySelector('[data-bhp-quiz-signup-error]');
    var signupFname = root.querySelector('[data-bhp-quiz-fname]');
    var signupEmail = root.querySelector('[data-bhp-quiz-email]');
    var signupHoneypot = root.querySelector('[data-bhp-quiz-signup] [name="bhp_website"]');
    // The Q1 pain answer, carried into every later event so a route can be
    // read together with the difficulty that produced it. Empty until Q1 is
    // answered, and cleared on restart.
    var currentPain = '';
    var signupBusy = false;
    var signupStartedFired = false;

    // --- Accessible naming + transition announcement (2026-07-30) --------
    //
    // The promotional header that used to sit above both question screens is
    // gone, so the visible question is now the honest title of each screen. It
    // is a real <h2>, and inside the sitewide modal the dialog's
    // aria-labelledby is retargeted to whichever heading is actually VISIBLE —
    // never a hidden step, which would name the dialog after content the
    // visitor cannot see.
    //
    // dialogEl is null on the homepage and the canonical /find-your-adventure/
    // page (no dialog there), so every call below is a harmless no-op in those
    // renders and no second code path is needed.
    var dialogEl = typeof root.closest === 'function' ? root.closest('[role="dialog"]') : null;
    // The markup's own persistent label ("Find Your Adventure quiz"), kept as
    // the fallback so the dialog can never end up with no accessible name.
    var fallbackLabelId = dialogEl ? dialogEl.getAttribute('aria-labelledby') : null;
    var announceEl = root.querySelector('[data-bhp-quiz-announce]');
    var q1Heading = root.querySelector('[data-bhp-quiz-step="1"] .bhp-quiz__question');
    var q2Heading = root.querySelector('[data-bhp-quiz-step="2"] .bhp-quiz__question');
    var q1Progress = root.querySelector('[data-bhp-quiz-step="1"] .bhp-quiz__progress');
    var q2Progress = root.querySelector('[data-bhp-quiz-step="2"] .bhp-quiz__progress');
    // Nothing is announced for the screen the visitor arrives on — only for
    // genuine transitions away from it. Flipped true once init finishes.
    var announceReady = false;
    var announceTimer = null;

    function headingForStep(name) {
      if (String(name) === '1') {
        return q1Heading;
      }
      if (String(name) === '2') {
        return q2Heading;
      }
      if (String(name) === 'result') {
        // P2-2: the headline is now the dominant element AND the first thing
        // on the screen, so it names the dialog for every route without a
        // conditional. The old branch preferred the offer heading and fell
        // through to the headline when that heading was hidden; there is no
        // hidden case left, and naming a dialog after a delivery mechanic
        // ("Your free … sent by email.") was never the better of the two.
        return resultTitle;
      }
      return null;
    }

    function syncDialogLabel(name) {
      if (!dialogEl) {
        return;
      }
      var heading = headingForStep(name);
      if (heading && heading.id) {
        dialogEl.setAttribute('aria-labelledby', heading.id);
      } else if (fallbackLabelId) {
        dialogEl.setAttribute('aria-labelledby', fallbackLabelId);
      }
    }

    /**
     * Speak "Question 2 of 2. <question>" once, politely, after a transition.
     * Cleared first and re-written on a short timer so an identical string
     * (e.g. Back to Question 1 twice) is still treated as a change and read
     * again — a live region that is written the same value twice is silent.
     *
     * Only the two question screens announce here. The result screen already
     * moves focus to its headline, which announces itself; adding a live
     * region there as well would say the same thing twice.
     */
    function announceStep(name) {
      if (!announceEl || !announceReady) {
        return;
      }
      var heading = (String(name) === '1' || String(name) === '2') ? headingForStep(name) : null;
      if (!heading) {
        announceEl.textContent = '';
        return;
      }
      var progressEl = String(name) === '1' ? q1Progress : q2Progress;
      var progressText = progressEl ? progressEl.textContent.trim() : '';
      var questionText = heading.textContent.trim();
      var message = progressText ? progressText + '. ' + questionText : questionText;

      announceEl.textContent = '';
      if (announceTimer !== null) {
        window.clearTimeout(announceTimer);
      }
      announceTimer = window.setTimeout(function () {
        announceTimer = null;
        announceEl.textContent = message;
      }, 60);
    }

    var hasStarted = false;
    var hasViewed = false;
    var selectedRouteKey = null;

    // --- Internal scroll container ------------------------------------
    //
    // Inside the sitewide modal the quiz element itself is the single
    // scrollable region (quiz-modal.css gives `.bhp-quiz-modal .bhp-quiz`
    // overflow-y:auto so the dialog can keep its close button pinned). On the
    // homepage and the canonical /find-your-adventure/ page nothing inside the
    // quiz scrolls, so this resolves to the root and every write below is a
    // harmless no-op there.
    //
    // Resolved lazily and memoised: at DOMContentLoaded the modal is still
    // `hidden`, and we only need the declared overflow, never a measurement.
    // The walk is bounded by the dialog so it can never reach — let alone
    // move — the page's own scroller.
    var scrollContainer;

    function getScrollContainer() {
      if (scrollContainer !== undefined) {
        return scrollContainer;
      }
      var dialog = typeof root.closest === 'function' ? root.closest('.bhp-quiz-modal__dialog') : null;
      scrollContainer = root;
      if (dialog) {
        var el = root;
        while (el && el !== dialog.parentElement) {
          var overflowY = window.getComputedStyle(el).overflowY;
          if (overflowY === 'auto' || overflowY === 'scroll') {
            scrollContainer = el;
            break;
          }
          el = el.parentElement;
        }
      }
      return scrollContainer;
    }

    // Every quiz screen must begin at its own top (2026-07-29). Swapping the
    // `hidden` steps does not touch the container's scrollTop, so a visitor who
    // scrolled down to reach the third or fourth Question 1 answer arrived at
    // Question 2 already scrolled — clipping the eyebrow, headline and intro
    // copy. Measured on staging 1.19.95 at 1024x420: Q1 scrollTop 90 carried
    // straight into Q2, pushing the eyebrow 38px above the visible area.
    //
    // Deliberately container-only: this never calls window.scrollTo() and never
    // touches the underlying page position, which is protected separately by
    // quiz-modal.js's open/close capture-and-restore.
    function resetInternalScroll() {
      var container = getScrollContainer();
      if (!container) {
        return;
      }
      container.scrollTop = 0;
      // The outgoing step leaves the flow in the same frame, so the container's
      // scrollHeight changes after this runs; re-assert once on the next frame
      // so a late clamp (or a focus fallback that ignored preventScroll) cannot
      // leave the new screen part-scrolled.
      if (typeof window.requestAnimationFrame === 'function') {
        window.requestAnimationFrame(function () {
          container.scrollTop = 0;
        });
      }
    }

    // Focus without letting the browser scroll the element into view. The quiz
    // can live inside a fixed-position modal, where a focus-driven scroll would
    // move the page behind it for no reason. Where FocusOptions is unsupported
    // the plain focus() fallback *will* scroll the internal container to reveal
    // the target, so the container's intended position is captured first and
    // put back afterwards — the modal's scroll state wins over the browser's
    // scroll-into-view, never the other way round.
    function focusQuietly(el) {
      if (!el || typeof el.focus !== 'function') {
        return;
      }
      var container = getScrollContainer();
      var savedTop = container ? container.scrollTop : 0;
      var savedLeft = container ? container.scrollLeft : 0;
      try {
        el.focus({ preventScroll: true });
      } catch (e) {
        el.focus();
      }
      if (container && (container.scrollTop !== savedTop || container.scrollLeft !== savedLeft)) {
        container.scrollTop = savedTop;
        container.scrollLeft = savedLeft;
      }
    }

    function showStep(name) {
      Object.keys(stepEls).forEach(function (key) {
        if (stepEls[key]) {
          stepEls[key].hidden = String(key) !== String(name);
        }
      });
      // Step-state classes (2026-07-31). The question screens must be able to
      // compact themselves — tighter rhythm, shorter answer cards, a wider
      // two-column grid — WITHOUT dragging the result screen down with them:
      // the result carries a real form and legitimately needs its own height.
      // A class per step is what keeps those two sets of rules apart.
      var stepName = String(name);
      root.classList.toggle('bhp-quiz--result', stepName === 'result');
      root.classList.toggle('bhp-quiz--step-1', stepName === '1');
      root.classList.toggle('bhp-quiz--step-2', stepName === '2');
      root.classList.toggle('bhp-quiz--question', stepName === '1' || stepName === '2');
      // The dialog is an ANCESTOR of this element, so CSS alone cannot widen it
      // from a class on the quiz root. Set it explicitly rather than depending on
      // :has() support: the question grid needs a wider dialog than the result
      // form does, so that two columns are wide enough for a long answer to wrap
      // in two lines instead of three.
      if (dialogEl) {
        dialogEl.classList.toggle('bhp-quiz-modal__dialog--question', stepName === '1' || stepName === '2');
      }
      // Name the dialog after the screen the visitor is actually looking at,
      // and announce the new question. Both run after the `hidden` swap above,
      // so headingForStep() only ever sees the live state.
      syncDialogLabel(name);
      announceStep(name);
      // Last, so it runs after the new screen is in the flow — and before any
      // caller moves focus. Every transition goes through here (intro→Q1,
      // Q1→Q2, Q2→result, Back, Start over), so no click handler needs its own
      // copy of this.
      resetInternalScroll();
    }

    function maybeFireViewed() {
      if (hasViewed) {
        return;
      }
      hasViewed = true;
      pushQuizEvent('quiz_viewed', {});
    }

    /**
     * QUESTION 1 - the pain answer. Routes nowhere, by design: no branch below
     * reads it to decide a destination, and none ever should. Q1 exists so a
     * visitor recognises themselves before being asked to classify themselves,
     * and so the company can eventually learn which difficulty is most common
     * per audience. It is a dimension, not a fork.
     */
    function selectPain(pain, moveFocus) {
      if (!pain) {
        return;
      }
      currentPain = pain;

      if (!hasStarted) {
        hasStarted = true;
        pushQuizEvent('quiz_started', {});
      }
      // No `quiz_audience` here. It would be a fabricated value: the visitor
      // has not told us who they are yet.
      pushQuizEvent('quiz_q1_answer', { quiz_pain: pain });

      q1Options.querySelectorAll('[data-bhp-quiz-pain]').forEach(function (btn) {
        btn.setAttribute('aria-checked', String(btn.getAttribute('data-bhp-quiz-pain') === pain));
      });

      showStep(2);
      if (moveFocus !== false) {
        focusQuietly(q2Options.querySelector('[data-bhp-quiz-route]'));
      }
    }

    // Renders the result body as a bold resource label followed by the plain
    // supporting detail (2026-07-30). Built from real DOM nodes -- an actual
    // <strong> element plus a text node -- so nothing is ever parsed from a
    // combined string: no innerHTML, no regex, no punctuation-splitting. The
    // <strong> is semantic, not decorative: the free resource IS the result.
    //
    // `resource` is intentionally optional. The organization group-order /
    // partnership answer routes to a conversation, not to a free download, so
    // it carries an empty resource and renders detail-only rather than
    // inventing an offer that doesn't exist.
    /**
     * The resource name now lives in its own heading (renderResultResource),
     * so this only ever writes the supporting explanation — no embedded
     * label, no leading em dash, no duplicated resource text.
     */
    function renderResultDetail(detail) {
      resultText.textContent = detail || '';
    }

    /**
     * Populate the offer line. Under REVISION B every route has one, including
     * organization - whose offer line's entire job is to say that the resource
     * is not ready. The hide branch survives for the empty-string case only,
     * so a future route added without copy renders nothing rather than an
     * empty paragraph with margins.
     */
    function renderResultResource(resource) {
      if (!resultResource) {
        return;
      }
      resultResource.textContent = resource || '';
      resultResource.hidden = !resource;
    }

    /**
     * QUESTION 2 - the router. This is where the audience becomes known, so
     * this is where `quiz_audience` first legitimately appears in a payload.
     */
    function onRouteAnswer(routeKey) {
      var route = routes[routeKey];
      if (!route) {
        return;
      }
      selectedRouteKey = routeKey;

      pushQuizEvent('quiz_q2_answer', { quiz_audience: route.audience, quiz_pain: currentPain });
      pushQuizEvent('quiz_completed', {
        quiz_audience: route.audience,
        routed_audience: route.audience,
        quiz_pain: currentPain,
      });

      resultTitle.textContent = route.result_title || '';
      renderResultDetail(route.result_detail || '');
      renderResultResource(route.result_resource || '');
      resultCta.textContent = route.cta_label || '';
      resultCta.href = withUtm(route.destination, utmParams);
      resultCta.setAttribute('data-bhp-source', 'audience_quiz');
      resultCta.setAttribute('data-bhp-quiz-audience', route.audience);
      resultCta.setAttribute('data-bhp-quiz-pain', currentPain);
      resultCta.setAttribute('data-bhp-entry-location', entryLocation);

      /*
       * THE SIGNUP GATE IS AN EXPLICIT FLAG, NOT AN INFERENCE FROM THE COPY.
       *
       * It used to be `resource !== ''` - show the form whenever the result
       * names a resource. Under REVISION B that test gives the WRONG answer
       * for organization, whose offer line is a non-empty sentence saying the
       * Community Reading Kit is not finished. Inferring a form from that
       * string would put an email capture directly beneath the sentence
       * explaining there is nothing to send, which is the precise broken
       * promise the copy was written to prevent. `offers_signup` states the
       * decision once, in the config, where a human can read it.
       */
      var offersSignup = route.offers_signup === true && !!route.signup_cta && !!signupForm;
      if (signupForm) {
        resetSignupForm();
        signupForm.hidden = !offersSignup;
        if (offersSignup) {
          signupSubmit.textContent = route.signup_cta;
          resultCta.hidden = true;
        } else {
          resultCta.hidden = false;
        }
      }

      showStep('result');
      focusQuietly(resultTitle);
    }

    // ----------------------------------------------------------------
    // Inline result signup
    // ----------------------------------------------------------------
    function setSignupError(message) {
      if (!signupError) {
        return;
      }
      if (!message) {
        signupError.hidden = true;
        signupError.textContent = '';
        return;
      }
      signupError.textContent = message;
      signupError.hidden = false;
    }

    function resetSignupForm() {
      if (!signupForm) {
        return;
      }
      setSignupError('');
      signupBusy = false;
      signupStartedFired = false;
      if (signupSubmit) {
        signupSubmit.disabled = false;
        signupSubmit.removeAttribute('aria-busy');
      }
      if (signupFname) { signupFname.value = ''; }
      if (signupEmail) { signupEmail.value = ''; }
    }

    // Deliberately permissive: the server is the authority on validity. This
    // only catches the obviously-empty/malformed case so the visitor gets an
    // instant answer instead of a round trip.
    function looksLikeEmail(value) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    if (signupForm) {
      // "Form started" — fired once per result, carrying no field content.
      ['input', 'focus'].forEach(function (evt) {
        signupForm.addEventListener(evt, function () {
          if (signupStartedFired || !selectedRouteKey) {
            return;
          }
          signupStartedFired = true;
          pushQuizEvent('quiz_signup_started', {
            quiz_audience: (routes[selectedRouteKey] || {}).audience || '',
            quiz_pain: currentPain,
          });
        }, true);
      });

      signupForm.addEventListener('submit', function (event) {
        event.preventDefault();

        if (signupBusy) {
          return; // Duplicate-submit guard.
        }

        var route = routes[selectedRouteKey];
        if (!route || !signupEmail) {
          return;
        }

        var email = (signupEmail.value || '').trim();
        if (!looksLikeEmail(email)) {
          setSignupError('Please enter a valid email address.');
          signupEmail.focus();
          return;
        }

        signupBusy = true;
        setSignupError('');
        signupSubmit.disabled = true;
        signupSubmit.setAttribute('aria-busy', 'true');

        pushQuizEvent('quiz_signup_submitted', {
          quiz_audience: route.audience,
          quiz_pain: currentPain,
        });

        // URLSearchParams keeps this a normal form-encoded POST body. The
        // email and first name travel in the BODY only — never in the URL,
        // never in analytics, never in storage.
        var body = new URLSearchParams();
        body.set('action', 'bhp_quiz_signup');
        body.set('nonce', signupNonce);
        body.set('route', selectedRouteKey);
        body.set('email', email);
        body.set('first_name', signupFname ? (signupFname.value || '').trim() : '');
        body.set('bhp_website', signupHoneypot ? signupHoneypot.value : '');

        fetch(signupUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: body.toString(),
        }).then(function (response) {
          return response.json().catch(function () { return { ok: false, code: 'error' }; });
        }).then(function (data) {
          if (data && data.ok && data.redirect) {
            pushQuizEvent('quiz_signup_success', {
              quiz_audience: route.audience,
              quiz_pain: currentPain,
            });
            // Mailchimp has accepted the subscriber AND the tag write at this
            // point. Delivery is asynchronous and is deliberately not awaited.
            window.location.assign(data.redirect);
            return;
          }

          var code = (data && data.code) || 'error';
          pushQuizEvent('quiz_signup_failed', {
            quiz_audience: route.audience,
            quiz_pain: currentPain,
            error_code: code, // Generic classifier only — never a provider message.
          });

          var messages = {
            invalid: 'Please enter a valid email address.',
            rate_limited: 'Too many attempts just now. Please try again in a few minutes.',
            unavailable: 'Signup is temporarily unavailable. Please try again later.',
          };
          setSignupError(messages[code] || 'We couldn’t complete your signup right now. Please try again in a moment.');

          // Stay on the result step with the visitor's entries intact.
          signupBusy = false;
          signupSubmit.disabled = false;
          signupSubmit.removeAttribute('aria-busy');
          // Move focus to the error so it is announced and reachable without
          // scrolling the page underneath the modal.
          if (typeof signupError.focus === 'function') {
            signupError.setAttribute('tabindex', '-1');
            focusQuietly(signupError);
          }
        }).catch(function () {
          pushQuizEvent('quiz_signup_failed', {
            quiz_audience: route.audience,
            quiz_pain: currentPain,
            error_code: 'network',
          });
          setSignupError('We couldn’t reach the server. Please check your connection and try again.');
          signupBusy = false;
          signupSubmit.disabled = false;
          signupSubmit.removeAttribute('aria-busy');
        });
      });
    }

    q1Options.addEventListener('click', function (event) {
      var btn = event.target.closest('[data-bhp-quiz-pain]');
      if (!btn) {
        return;
      }
      selectPain(btn.getAttribute('data-bhp-quiz-pain'));
    });

    // Q2's four route buttons are server-rendered now, so one delegated
    // listener replaces the per-button listeners the old option-builder
    // attached. Same aria-checked bookkeeping, no innerHTML anywhere.
    q2Options.addEventListener('click', function (event) {
      var btn = event.target.closest('[data-bhp-quiz-route]');
      if (!btn) {
        return;
      }
      q2Options.querySelectorAll('[data-bhp-quiz-route]').forEach(function (sibling) {
        sibling.setAttribute('aria-checked', String(sibling === btn));
      });
      onRouteAnswer(btn.getAttribute('data-bhp-quiz-route'));
    });

    if (backBtn) {
      backBtn.addEventListener('click', function () {
        showStep(1);
        // Return focus to the pain answer the visitor actually chose, not to
        // the first button, so Back lands where they left off.
        var current = currentPain
          ? q1Options.querySelector('[data-bhp-quiz-pain="' + currentPain + '"]')
          : null;
        focusQuietly(current || q1Options.querySelector('[data-bhp-quiz-pain]'));
      });
    }

    if (restartBtn) {
      restartBtn.addEventListener('click', function () {
        pushQuizEvent('quiz_restarted', { quiz_last_route: selectedRouteKey || '' });
        selectedRouteKey = null;
        currentPain = '';
        // Clear any entered name/email so a restart never leaves one
        // visitor's details sitting in the form for the next interaction.
        resetSignupForm();
        // BOTH radiogroups are cleared. Q2's buttons are server-rendered and
        // persist across a restart now, so leaving them checked would show the
        // previous visitor's audience already selected on the router screen.
        q1Options.querySelectorAll('[data-bhp-quiz-pain]').forEach(function (btn) {
          btn.setAttribute('aria-checked', 'false');
        });
        q2Options.querySelectorAll('[data-bhp-quiz-route]').forEach(function (btn) {
          btn.setAttribute('aria-checked', 'false');
        });
        showStep(1);
        focusQuietly(q1Options.querySelector('[data-bhp-quiz-pain]'));
      });
    }

    var introEl = root.querySelector('[data-bhp-quiz-intro]');
    var startBtn = root.querySelector('[data-bhp-quiz-start]');

    // Initial accessible name. Without an intro gate the quiz opens on
    // Question 1, so the dialog is named by that visible question from the
    // very first frame rather than only after the first transition. With an
    // intro gate (homepage) there is no dialog at all, so this is a no-op.
    if (!introEl) {
      syncDialogLabel(1);
      // The quiz renders on Question 1, but showStep() has not run yet, so the
      // step-state classes it normally applies are not on the element. Set the
      // arrival state explicitly or the first screen a visitor ever sees would
      // miss the question-step compaction until their first transition.
      root.classList.add('bhp-quiz--step-1', 'bhp-quiz--question');
      if (dialogEl) {
        dialogEl.classList.add('bhp-quiz-modal__dialog--question');
      }
    }
    // Everything above is the arrival state; only real transitions announce.
    announceReady = true;
    if (introEl && startBtn) {
      startBtn.addEventListener('click', function () {
        pushQuizEvent('homepage_quiz_started', {
          source: 'homepage',
          destination: 'audience_quiz',
          cta_text: startBtn.textContent.trim(),
        });
        introEl.hidden = true;
        showStep(1);
        focusQuietly(q1Options.querySelector('[data-bhp-quiz-pain]'));
      });
    }

    // Fire quiz_viewed once the quiz actually scrolls into view, not merely on page load.
    if ('IntersectionObserver' in window) {
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            maybeFireViewed();
            observer.disconnect();
          }
        });
      }, { threshold: 0.4 });
      observer.observe(root);
    } else {
      maybeFireViewed();
    }

    // Best-effort abandonment signal: started but never reached the result step, on page unload.
    window.addEventListener('beforeunload', function () {
      if (hasStarted && stepEls.result && stepEls.result.hidden) {
        pushQuizEvent('quiz_abandoned', { quiz_last_route: selectedRouteKey || '' });
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bhp-quiz]').forEach(initQuiz);
  });
})();
