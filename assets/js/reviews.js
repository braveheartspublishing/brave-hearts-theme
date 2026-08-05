/*
 * Brave Hearts — customer review system, progressive enhancement only.
 *
 * NOTHING IN THIS FILE IS LOAD-BEARING, and that is a deliberate constraint
 * rather than a description. The form works with JavaScript disabled: the star
 * picker is five real radio inputs, the fields are native, submission is a
 * normal HTML POST to wp-comments-post.php, and every rule this file enforces
 * in the page is enforced again — decisively — on the server in
 * bhp_review_intercept_submission(). If this file fails to load, a reviewer
 * gets a slower round trip, not a broken form and not a form that lets an
 * invalid review through.
 *
 * It does four things:
 *   1. VALIDATES IN PAGE before submit (CYCLE142-CX-071, CYCLE142-CX-072), so a
 *      missing rating or a mistyped email never becomes a full-page POST that
 *      lands on WordPress's unstyled wp_die() error screen. The wording comes
 *      from PHP via a JSON block inside the form, so the two ends cannot drift
 *      and translations keep working.
 *   2. Puts the cursor in the text box on the dedicated /review/ page, so the
 *      email route really is "click, type, submit" — and re-asserts that on the
 *      product page, where it was measured NOT to happen (CYCLE142-CX-074).
 *   3. Moves focus to the thank-you panel after a submission, so a screen
 *      reader user is told the review was sent.
 *   4. Moves focus to the validation summary after a server-side rejection, for
 *      the same reason.
 */
(function () {
  'use strict';

  /* Fields this file knows how to mark. `rating` is the radio group; the rest
     are ordinary inputs addressed by name. */
  var FIELDS = ['rating', 'comment', 'author', 'email'];

  /* Deliberately permissive. This is a typo-catcher, not an authority — the
     server's is_email() is what actually decides, and a client-side regex that
     is stricter than the server rejects addresses that would have worked. */
  var EMAIL_SHAPE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  function contextOf(form) {
    return form.getAttribute('data-bhp-review-form') === 'standalone' ? 'standalone' : 'product';
  }

  function uidOf(form) {
    return 'bhp-review-' + contextOf(form);
  }

  function panelOf(form) {
    return document.getElementById('bhp-review-errors-' + contextOf(form));
  }

  function messagesOf(form) {
    var node = form.querySelector('.bhp-review-form__messages');
    if (!node) {
      return {};
    }
    try {
      return JSON.parse(node.textContent) || {};
    } catch (e) {
      return {};
    }
  }

  function focusSafely(node, preventScroll) {
    if (!node) {
      return;
    }
    try {
      node.focus({ preventScroll: !!preventScroll });
    } catch (e) {
      node.focus();
    }
  }

  // ----------------------------------------------------------------
  // Validation
  // ----------------------------------------------------------------

  /* Mirrors the server's rules in bhp_review_intercept_submission(). Where the
     two could disagree, the server wins by construction — it runs second. */
  function validate(form) {
    var uid = uidOf(form);
    var msg = messagesOf(form);
    var found = [];

    if (
      form.getAttribute('data-bhp-rating-required') === '1' &&
      !form.querySelector('input[name="rating"]:checked')
    ) {
      found.push({ field: 'rating', anchor: uid + '-rating-5', message: msg.rating });
    }

    var comment = form.querySelector('[name="comment"]');
    if (comment && !comment.value.trim()) {
      found.push({ field: 'comment', anchor: uid + '-comment', message: msg.comment });
    }

    /* Absent for a logged-in reviewer — the template does not render them, so a
       null here is the normal case, not a failure. */
    var author = form.querySelector('[name="author"]');
    if (author && !author.value.trim()) {
      found.push({ field: 'author', anchor: uid + '-author', message: msg.author });
    }

    var email = form.querySelector('[name="email"]');
    if (email) {
      var value = email.value.trim();
      if (!value) {
        found.push({ field: 'email', anchor: uid + '-email', message: msg.email });
      } else if (!EMAIL_SHAPE.test(value)) {
        found.push({ field: 'email', anchor: uid + '-email', message: msg.email_invalid });
      }
    }

    return found;
  }

  function clearField(form, field) {
    var errEl = document.getElementById(uidOf(form) + '-' + field + '-error');
    if (errEl) {
      errEl.hidden = true;
      errEl.textContent = '';
    }

    if (field === 'rating') {
      var group = form.querySelector('.bhp-star-input');
      if (group) {
        group.removeAttribute('data-bhp-invalid');
        group.removeAttribute('aria-describedby');
      }
    } else {
      var input = form.querySelector('[name="' + field + '"]');
      if (input) {
        input.removeAttribute('aria-invalid');
      }
    }

    /* Once the reviewer has fixed everything the summary is stale, so it goes.
       Leaving a list of solved problems on screen reads as "still broken". */
    var remaining = form.querySelectorAll('.bhp-review-form__error:not([hidden])');
    var panel = panelOf(form);
    if (panel && remaining.length === 0) {
      panel.hidden = true;
      var list = panel.querySelector('.bhp-review-form__errors-list');
      if (list) {
        list.innerHTML = '';
      }
    }
  }

  /* Renders exactly the markup review-form.php renders on a server-side
     rejection, so the two paths are visually and semantically identical. */
  function paint(form, found) {
    var uid = uidOf(form);

    FIELDS.forEach(function (field) {
      clearField(form, field);
    });

    var panel = panelOf(form);
    var list = panel ? panel.querySelector('.bhp-review-form__errors-list') : null;
    if (list) {
      list.innerHTML = '';
    }

    found.forEach(function (item) {
      var errEl = document.getElementById(uid + '-' + item.field + '-error');
      if (errEl && item.message) {
        errEl.textContent = item.message;
        errEl.hidden = false;
      }

      if (item.field === 'rating') {
        var group = form.querySelector('.bhp-star-input');
        if (group) {
          group.setAttribute('data-bhp-invalid', 'true');
          group.setAttribute('aria-describedby', uid + '-rating-error');
        }
      } else {
        var input = form.querySelector('[name="' + item.field + '"]');
        if (input) {
          input.setAttribute('aria-invalid', 'true');
        }
      }

      if (list && item.message) {
        var li = document.createElement('li');
        var link = document.createElement('a');
        link.href = '#' + item.anchor;
        link.textContent = item.message;
        li.appendChild(link);
        list.appendChild(li);
      }
    });

    if (panel) {
      panel.hidden = found.length === 0;
      if (found.length) {
        /* No preventScroll here, on purpose: this path has no URL fragment to
           scroll the page, so the focus call is what brings the summary into
           view. `.bhp-review-form__errors` carries scroll-margin-top so it
           lands clear of the sticky header. */
        focusSafely(panel, false);
      }
    }
  }

  function wire(form) {
    form.addEventListener('submit', function (event) {
      var found = validate(form);
      if (found.length) {
        event.preventDefault();
        paint(form, found);
      }
    });

    form.addEventListener('change', function (event) {
      if (event.target && event.target.name === 'rating') {
        clearField(form, 'rating');
      }
    });

    ['comment', 'author', 'email'].forEach(function (field) {
      var input = form.querySelector('[name="' + field + '"]');
      if (input) {
        input.addEventListener('input', function () {
          clearField(form, field);
        });
      }
    });
  }

  // ----------------------------------------------------------------
  // Focus
  // ----------------------------------------------------------------

  function focusTextarea(context) {
    var field = document.querySelector('[data-bhp-review-textarea="' + context + '"]');
    if (!field) {
      return false;
    }
    focusSafely(field, true);
    return true;
  }

  /*
   * CYCLE142-CX-074 — the product-page autofocus this file documents did not
   * happen. Measured live: arriving at `…-paperback/#write-review`,
   * `document.activeElement` was BODY on all fourteen samples across ~9 seconds,
   * while every precondition held and the identical call run by hand after load
   * worked and stuck. So the call is fine and something on the product page
   * takes focus back after DOMContentLoaded — the gallery, the consent banner or
   * another script. The root cause was not isolated and is not guessed at here.
   *
   * The fix is to stop competing on a single attempt: re-assert briefly after
   * load, and STOP the moment anything else legitimately holds focus. That last
   * condition is what keeps this from becoming a focus thief — if the reviewer
   * has clicked into a field, or the consent banner's shadow root has focus (in
   * which case activeElement is the host element, not BODY), this does nothing.
   */
  function assertProductFocus() {
    var hash = window.location.hash;
    if (hash !== '#write-review' && hash !== '#reviews') {
      return;
    }
    var field = document.querySelector('[data-bhp-review-textarea="product"]');
    if (!field) {
      return;
    }

    var attempts = 0;
    (function attempt() {
      var active = document.activeElement;
      if (active === field) {
        return; // Landed, and it stuck.
      }
      if (active && active !== document.body && active !== document.documentElement) {
        return; // Something else owns focus. Leave it alone.
      }
      focusSafely(field, true);
      attempts += 1;
      if (attempts < 6) {
        window.setTimeout(attempt, 120);
      }
    })();
  }

  // ----------------------------------------------------------------

  function init() {
    var forms = document.querySelectorAll('[data-bhp-review-form]');
    Array.prototype.forEach.call(forms, wire);

    /* A server-side rejection. The redirect already carries a fragment pointing
       at the summary, so scrolling is done — this only moves focus, which is
       what makes `role="alert"` reach a screen reader reliably. */
    var openPanel = document.querySelector('.bhp-review-form__errors:not([hidden])');
    if (openPanel) {
      focusSafely(openPanel, true);
      return;
    }

    var thanks = document.getElementById('bhp-review-thanks');
    if (thanks) {
      // The panel carries role="status" and tabindex="-1"; focusing it is what
      // makes the confirmation reach assistive technology reliably.
      focusSafely(thanks, true);
      return;
    }

    // Dedicated review page: the text box is the whole point of the page.
    if (document.querySelector('.bhp-review-page')) {
      focusTextarea('standalone');
      return;
    }

    // Product page: only when the visitor was deep-linked to the review area.
    assertProductFocus();

    /* And once more after everything else on the product page has run. Guarded
       exactly as above, so it is a no-op unless focus is still nowhere. */
    window.addEventListener('load', function () {
      if (!document.querySelector('.bhp-review-page') && !document.getElementById('bhp-review-thanks')) {
        assertProductFocus();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
